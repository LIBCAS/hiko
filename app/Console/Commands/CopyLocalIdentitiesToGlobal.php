<?php

namespace App\Console\Commands;

use App\Services\LocalIdentityGlobalCopyService;
use Illuminate\Console\Command;

class CopyLocalIdentitiesToGlobal extends Command
{
    protected $signature = 'hiko:copy-local-identities-to-global
        {--tenant=* : Tenant table prefix to process, e.g. hiko-test10. May be used multiple times.}
        {--chunk=500 : Number of local identities processed per chunk.}
        {--dry-run : Show what would be changed without writing to the database.}
        {--yes : Skip the confirmation prompt.}';

    protected $description = 'Copy unique tenant-local identity records into global_identities and link locals to their global matches.';

    public function handle(LocalIdentityGlobalCopyService $service): int
    {
        if (!$this->option('dry-run') && !$this->option('yes') && !$this->confirm(
            'This will create/update global identities, copy global professions/religions, and set local global_identity_id values. Continue?'
        )) {
            $this->warn('Cancelled.');
            return self::SUCCESS;
        }

        $this->info($this->option('dry-run')
            ? 'Starting dry run. No database changes will be written.'
            : 'Starting local identity copy.');

        $stats = $service->run([
            'dry_run' => (bool)$this->option('dry-run'),
            'tenants' => (array)$this->option('tenant'),
            'chunk' => (int)$this->option('chunk'),
        ]);

        foreach ($stats['tenant_results'] as $tenantPrefix => $tenantStats) {
            $this->line(sprintf(
                '%s: seen %d, created %d, matched %d, linked %d, professions %d, religions %d, notes %d, admin notes %d%s',
                $tenantPrefix,
                $tenantStats['local_seen'],
                $tenantStats['global_created'],
                $tenantStats['global_matched'],
                $tenantStats['local_links_updated'],
                $tenantStats['professions_inserted'],
                $tenantStats['religions_inserted'],
                $tenantStats['notes_updated'],
                $tenantStats['admin_notes_updated'],
                $tenantStats['missing_identity_table'] ? ' (missing identities table)' : ''
            ));
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Tenants total', $stats['tenants_total']],
                ['Tenants processed', $stats['tenants_processed']],
                ['Local identities seen', $stats['local_seen']],
                ['Global identities created', $stats['global_created']],
                ['Global identities matched', $stats['global_matched']],
                ['Notes updated', $stats['notes_updated']],
                ['Admin notes updated', $stats['admin_notes_updated']],
                ['Global professions inserted', $stats['professions_inserted']],
                ['Religions inserted', $stats['religions_inserted']],
                ['Local links updated', $stats['local_links_updated']],
                ['Missing tenant identity tables', $stats['missing_identity_table']],
            ]
        );

        $this->info($this->option('dry-run') ? 'Dry run finished.' : 'Copy finished.');

        return self::SUCCESS;
    }
}
