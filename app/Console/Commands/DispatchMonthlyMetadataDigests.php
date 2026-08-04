<?php

namespace App\Console\Commands;

use App\Services\MetadataDigestDispatcher;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class DispatchMonthlyMetadataDigests extends Command
{
    protected $signature = 'metadata-digests:dispatch-monthly';

    protected $description = 'Create recipient deliveries for the previous calendar month';

    public function handle(MetadataDigestDispatcher $dispatcher): int
    {
        if (!config('metadata_digest.enabled')) {
            $this->info('Metadata digest delivery is disabled.');

            return self::SUCCESS;
        }

        $end = CarbonImmutable::now(config('metadata_digest.timezone'))->startOfMonth();
        $start = $end->subMonth();
        $run = $dispatcher->dispatchScheduled($start, $end);

        $this->info("Metadata digest run #{$run->getKey()} is ready for processing.");

        return self::SUCCESS;
    }
}
