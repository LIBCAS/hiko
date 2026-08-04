<?php

namespace App\Services;

use App\Exports\MetadataDigestWorkbook;
use App\Mail\MetadataDigestMail;
use App\Models\MetadataDigestDelivery;
use App\Models\MetadataDigestRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use Throwable;

class MetadataDigestProcessor
{
    public function __construct(private readonly MetadataDigestCollector $collector)
    {
    }

    public function processPending(?int $limit = null): int
    {
        if (!config('metadata_digest.enabled')) {
            return 0;
        }

        $processed = 0;
        $limit ??= config('metadata_digest.process_limit', 5);

        while ($processed < $limit && ($delivery = $this->claimNext())) {
            $this->process($delivery);
            $processed++;
        }

        return $processed;
    }

    private function claimNext(): ?MetadataDigestDelivery
    {
        return DB::connection('mysql')->transaction(function () {
            $delivery = MetadataDigestDelivery::query()
                ->where('status', MetadataDigestDelivery::STATUS_PENDING)
                ->where(function ($query) {
                    $query->whereNull('available_at')->orWhere('available_at', '<=', now());
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$delivery) {
                return null;
            }

            $delivery->update([
                'status' => MetadataDigestDelivery::STATUS_PROCESSING,
                'attempts' => $delivery->attempts + 1,
                'started_at' => now(),
                'error_message' => null,
            ]);
            $delivery->run()->update(['status' => MetadataDigestRun::STATUS_PROCESSING]);

            return $delivery->fresh('run');
        });
    }

    private function process(MetadataDigestDelivery $delivery): void
    {
        $originalLocale = app()->getLocale();
        $locale = in_array($delivery->locale, ['cs', 'en'], true)
            ? $delivery->locale
            : config('app.locale', 'cs');
        app()->setLocale($locale);

        try {
            $digest = $this->collector->collect($delivery);
            $attachments = $this->attachments($digest);
            $attachmentBytes = array_sum(array_map(
                fn(array $attachment) => strlen($attachment['data']),
                $attachments
            ));

            if ($attachmentBytes > config('metadata_digest.max_attachment_bytes')) {
                throw new \RuntimeException(
                    "Metadata digest attachments have {$attachmentBytes} bytes and exceed the configured limit."
                );
            }

            Mail::to($delivery->recipient_email)->send(
                (new MetadataDigestMail($digest, $attachments))->locale($locale)
            );

            $delivery->update([
                'status' => MetadataDigestDelivery::STATUS_SENT,
                'sent_at' => now(),
                'totals' => collect($digest['sections'])
                    ->map(fn(array $section) => $section['total'])
                    ->all(),
                'error_message' => null,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $maxAttempts = config('metadata_digest.max_attempts', 3);
            $willRetry = $delivery->attempts < $maxAttempts;
            $delivery->update([
                'status' => $willRetry
                    ? MetadataDigestDelivery::STATUS_PENDING
                    : MetadataDigestDelivery::STATUS_FAILED,
                'available_at' => $willRetry
                    ? now()->addMinutes(config('metadata_digest.retry_after_minutes', 15))
                    : null,
                'error_message' => Str::limit($exception->getMessage(), 2000),
            ]);
        } finally {
            app()->setLocale($originalLocale);
            $this->refreshRunStatus($delivery->run);
        }
    }

    private function attachments(array $digest): array
    {
        $dateSuffix = $digest['period_start']->format('Ymd') . '-' . $digest['period_end']->format('Ymd');

        return collect($digest['sections'])
            ->filter(fn(array $section) => $section['total'] > 0)
            ->map(function (array $section, string $key) use ($dateSuffix) {
                return [
                    'name' => "{$key}-{$dateSuffix}.xlsx",
                    'data' => ExcelFacade::raw(new MetadataDigestWorkbook($section), Excel::XLSX),
                ];
            })
            ->values()
            ->all();
    }

    private function refreshRunStatus(MetadataDigestRun $run): void
    {
        $counts = $run->deliveries()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $pending = ($counts[MetadataDigestDelivery::STATUS_PENDING] ?? 0)
            + ($counts[MetadataDigestDelivery::STATUS_PROCESSING] ?? 0);
        $sent = $counts[MetadataDigestDelivery::STATUS_SENT] ?? 0;
        $failed = $counts[MetadataDigestDelivery::STATUS_FAILED] ?? 0;

        $status = match (true) {
            $pending > 0 => MetadataDigestRun::STATUS_PROCESSING,
            $failed === 0 => MetadataDigestRun::STATUS_COMPLETED,
            $sent > 0 => MetadataDigestRun::STATUS_PARTIAL,
            default => MetadataDigestRun::STATUS_FAILED,
        };

        $run->update(['status' => $status]);
    }
}
