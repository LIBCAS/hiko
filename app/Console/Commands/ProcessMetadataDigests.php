<?php

namespace App\Console\Commands;

use App\Services\MetadataDigestProcessor;
use Illuminate\Console\Command;

class ProcessMetadataDigests extends Command
{
    protected $signature = 'metadata-digests:process {--limit= : Maximum deliveries to process}';

    protected $description = 'Process pending metadata digest deliveries without a queue worker';

    public function handle(MetadataDigestProcessor $processor): int
    {
        if (!config('metadata_digest.enabled')) {
            $this->info('Metadata digest delivery is disabled.');

            return self::SUCCESS;
        }

        $limit = $this->option('limit');
        $processed = $processor->processPending($limit === null ? null : max(1, (int) $limit));
        $this->info("Processed {$processed} metadata digest delivery/deliveries.");

        return self::SUCCESS;
    }
}
