<?php

namespace App\Console\Commands;

use App\Services\LocalIdentityGlobalCopyService;
use Illuminate\Console\Command;

class CopyLocalIdentitiesToGlobal extends Command
{
    protected $signature = 'hiko:copy-local-identities-to-global
        {--tenant=* : Tenant table prefix to process, e.g. hiko-test10. May be used multiple times.}
        {--chunk=500 : Number of local identities processed per chunk.}
        {--reset : Unlink and delete all generated global identity data instead of copying.}
        {--type= : Limit reset to person or institution. Only valid with --reset.}
        {--dry-run : Show what would be changed without writing to the database.}
        {--yes : Skip the confirmation prompt.}';

    protected $description = 'Copy unique tenant-local identity records into global_identities and link locals to their global matches.';

    public function handle(LocalIdentityGlobalCopyService $service): int
    {
        if ($this->option('reset')) {
            return $this->reset($service);
        }

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
                ['Non-person identities skipped', $stats['local_skipped_non_person']],
                ['Already-linked persons skipped', $stats['local_already_linked']],
                ['Persons with incomplete matching data', $stats['local_incomplete_match_data']],
                ['Global identities created', $stats['global_created']],
                ['Global identities matched', $stats['global_matched']],
                ['Global metadata updated', $stats['metadata_updated']],
                ['Gender conflicts kept for review', $stats['gender_conflicts']],
                ['Ambiguous date matches not linked', $stats['ambiguous_date_matches']],
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

    private function reset(LocalIdentityGlobalCopyService $service): int
    {
        $dryRun = (bool)$this->option('dry-run');
        $type = trim((string)$this->option('type'));

        if ($type !== '' && !in_array($type, ['person', 'institution'], true)) {
            $this->error('The --type option must be person or institution.');
            return self::INVALID;
        }

        if (!$dryRun && !$this->option('yes') && !$this->confirm(
            $type === ''
                ? 'This will unlink every local/global identity reference and delete all global identities and their pivots. Continue?'
                : "This will unlink and delete every global identity of type {$type} and its pivots. Continue?"
        )) {
            $this->warn('Cancelled.');
            return self::SUCCESS;
        }

        $stats = $service->reset([
            'dry_run' => $dryRun,
            'type' => $type === '' ? null : $type,
        ]);

        $this->table(
            ['Reset item', 'Count'],
            [
                ['Identity type', $stats['type'] ?? 'all'],
                ['Local identity links', $stats['local_identity_links']],
                ['Identity-letter links', $stats['identity_letter_links']],
                ['Global identity professions', $stats['global_identity_professions']],
                ['Global identity religions', $stats['global_identity_religions']],
                ['Global identity keywords', $stats['global_identity_keywords']],
                ['Global identities', $stats['global_identities']],
            ]
        );

        $this->info($dryRun ? 'Reset dry run finished.' : 'Global identity data reset finished.');

        return self::SUCCESS;
    }
}
