<?php

namespace App\Services;

use App\Models\MetadataDigestDelivery;
use App\Models\MetadataDigestRun;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class MetadataDigestDispatcher
{
    public function __construct(private readonly MetadataDigestRecipientResolver $recipients)
    {
    }

    public function dispatchScheduled(CarbonInterface $start, CarbonInterface $end): ?MetadataDigestRun
    {
        if (!config('metadata_digest.enabled')) {
            return null;
        }

        $key = sprintf('scheduled:%s:%s', $start->toIso8601String(), $end->toIso8601String());

        return DB::connection('mysql')->transaction(function () use ($start, $end, $key) {
            $run = MetadataDigestRun::query()->firstOrCreate(
                ['deduplication_key' => $key],
                [
                    'trigger' => MetadataDigestRun::TRIGGER_SCHEDULED,
                    'period_start' => $start,
                    'period_end' => $end,
                    'status' => MetadataDigestRun::STATUS_PENDING,
                ]
            );

            if (!$run->wasRecentlyCreated) {
                return $run;
            }

            foreach ($this->recipients->all() as $recipient) {
                $this->createDelivery($run, $recipient, config('app.locale', 'cs'));
            }

            if (!$run->deliveries()->exists()) {
                $run->update(['status' => MetadataDigestRun::STATUS_COMPLETED]);
            }

            return $run;
        });
    }

    public function dispatchManual(
        User $user,
        int $tenantId,
        CarbonInterface $start,
        CarbonInterface $end
    ): MetadataDigestRun {
        if (!config('metadata_digest.enabled')) {
            throw new \RuntimeException(__('hiko.metadata_digest_disabled'));
        }

        $recipient = $this->recipients->forEmail($user->email);

        if (!$recipient) {
            throw new \RuntimeException(__('hiko.metadata_digest_no_active_membership'));
        }

        $locale = app()->getLocale();

        return DB::connection('mysql')->transaction(function () use (
            $user,
            $tenantId,
            $start,
            $end,
            $recipient,
            $locale
        ) {
            $run = MetadataDigestRun::query()->create([
                'trigger' => MetadataDigestRun::TRIGGER_MANUAL,
                'period_start' => $start,
                'period_end' => $end,
                'status' => MetadataDigestRun::STATUS_PENDING,
                'requested_from_tenant_id' => $tenantId,
                'requested_by_user_id' => $user->getKey(),
                'requested_by_name' => $user->name,
                'requested_by_email' => $user->email,
            ]);

            $this->createDelivery($run, $recipient, $locale);

            return $run;
        });
    }

    /** @param array{email:string, normalized_email:string, name:?string, tenant_ids:array<int>} $recipient */
    private function createDelivery(MetadataDigestRun $run, array $recipient, string $locale): void
    {
        $locale = in_array($locale, ['cs', 'en'], true)
            ? $locale
            : config('app.locale', 'cs');

        MetadataDigestDelivery::query()->create([
            'metadata_digest_run_id' => $run->getKey(),
            'recipient_email' => $recipient['email'],
            'normalized_email' => $recipient['normalized_email'],
            'recipient_name' => $recipient['name'],
            'locale' => $locale,
            'tenant_ids' => $recipient['tenant_ids'],
            'status' => MetadataDigestDelivery::STATUS_PENDING,
            'available_at' => now(),
        ]);
    }
}
