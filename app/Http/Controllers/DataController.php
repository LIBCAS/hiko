<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMetadataDigestRequest;
use App\Models\MetadataDigestDelivery;
use App\Services\MetadataDigestDispatcher;
use App\Services\MetadataDigestRecipientResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DataController extends Controller
{
    public function index(MetadataDigestRecipientResolver $recipients): View
    {
        $timezone = config('metadata_digest.timezone');
        $periodEnd = CarbonImmutable::now($timezone)->startOfMonth();
        $periodStart = $periodEnd->subMonth();
        $normalizedEmail = $recipients->normalize(auth()->user()->email);

        return view('pages.data.index', [
            'title' => __('hiko.data_tools'),
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'digestEnabled' => (bool) config('metadata_digest.enabled'),
            'deliveries' => MetadataDigestDelivery::query()
                ->with('run')
                ->where('normalized_email', $normalizedEmail)
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    public function storeDigest(
        StoreMetadataDigestRequest $request,
        MetadataDigestDispatcher $dispatcher
    ): RedirectResponse {
        $timezone = config('metadata_digest.timezone');
        $start = CarbonImmutable::parse($request->validated('period_start'), $timezone);
        $end = CarbonImmutable::parse($request->validated('period_end'), $timezone);

        try {
            $run = $dispatcher->dispatchManual(
                $request->user(),
                (int) tenancy()->tenant->getKey(),
                $start,
                $end
            );
        } catch (\RuntimeException $exception) {
            return redirect()->route('data.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('data.index')->with(
            'success',
            __('hiko.metadata_digest_requested', ['id' => $run->getKey()])
        );
    }
}
