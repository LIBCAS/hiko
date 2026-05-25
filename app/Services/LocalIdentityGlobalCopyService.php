<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LocalIdentityGlobalCopyService
{
    public const NOTE_SEPARATOR = "\n\n===\n\n";

    private const DUPLICATE_FIELDS = [
        'name',
        'surname',
        'forename',
        'general_name_modifier',
        'alternative_names',
        'related_names',
        'type',
        'nationality',
        'gender',
        'birth_year',
        'death_year',
        'related_identity_resources',
    ];

    private const COPY_FIELDS = [
        'name',
        'surname',
        'forename',
        'general_name_modifier',
        'alternative_names',
        'related_names',
        'type',
        'nationality',
        'gender',
        'birth_year',
        'death_year',
        'related_identity_resources',
        'viaf_id',
        'note',
    ];

    private array $globalIdentityIdsByKey = [];

    private int $nextDryRunId = -1;

    public function run(array $options = []): array
    {
        $dryRun = (bool)($options['dry_run'] ?? false);
        $tenantPrefixes = $this->tenantPrefixes($options['tenants'] ?? []);
        $chunkSize = max(1, (int)($options['chunk'] ?? 500));

        $this->loadGlobalIdentityKeys();

        $totals = $this->emptyStats();
        $totals['tenants_total'] = count($tenantPrefixes);

        foreach ($tenantPrefixes as $tenantPrefix) {
            $tenantStats = $this->processTenant($tenantPrefix, $dryRun, $chunkSize);
            $totals['tenants_processed']++;

            foreach ($tenantStats as $key => $value) {
                if (is_int($value)) {
                    $totals[$key] = ($totals[$key] ?? 0) + $value;
                }
            }

            $totals['tenant_results'][$tenantPrefix] = $tenantStats;
        }

        return $totals;
    }

    public function tenantPrefixes(array $requestedTenants = []): array
    {
        $requestedTenants = collect($requestedTenants)
            ->filter()
            ->map(fn($tenant) => trim((string)$tenant))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($requestedTenants !== []) {
            return $requestedTenants;
        }

        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'table_prefix')) {
            $prefixes = DB::table('tenants')
                ->whereNotNull('table_prefix')
                ->orderBy('table_prefix')
                ->pluck('table_prefix')
                ->map(fn($prefix) => trim((string)$prefix))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($prefixes !== []) {
                return $prefixes;
            }
        }

        $mappingPath = base_path('tenant_mapping.json');
        if (!is_file($mappingPath)) {
            return [];
        }

        $mapping = json_decode((string)file_get_contents($mappingPath), true);
        if (!is_array($mapping)) {
            return [];
        }

        return collect(array_values($mapping))
            ->map(fn($prefix) => trim((string)$prefix))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function duplicateKey(object|array $identity): string
    {
        $identity = (array)$identity;

        return json_encode(
            collect(self::DUPLICATE_FIELDS)
                ->mapWithKeys(fn($field) => [$field => $this->normalizeForDuplicateKey($identity[$field] ?? null)])
                ->all(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    public function normalizeForDuplicateKey(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string)$value;
        $trimmed = trim($value);

        if ($trimmed === '' || $trimmed === '[]') {
            return null;
        }

        return $value;
    }

    private function processTenant(string $tenantPrefix, bool $dryRun, int $chunkSize): array
    {
        $identitiesTable = "{$tenantPrefix}__identities";
        $professionTable = "{$tenantPrefix}__identity_profession";
        $religionTable = "{$tenantPrefix}__identity_religion";

        $stats = $this->emptyStats();

        if (!Schema::hasTable($identitiesTable)) {
            $stats['missing_identity_table'] = 1;
            return $stats;
        }

        $query = DB::table($identitiesTable)->orderBy('id');

        $query->chunk($chunkSize, function ($localIdentities) use (
            $tenantPrefix,
            $identitiesTable,
            $professionTable,
            $religionTable,
            $dryRun,
            &$stats
        ) {
            foreach ($localIdentities as $localIdentity) {
                $stats['local_seen']++;

                $key = $this->duplicateKey($localIdentity);
                $globalIdentityId = $this->globalIdentityIdsByKey[$key] ?? null;
                $created = false;

                if ($globalIdentityId === null) {
                    $created = true;
                    $stats['global_created']++;
                    $globalIdentityId = $dryRun
                        ? $this->nextDryRunId--
                        : $this->createGlobalIdentity($localIdentity, $tenantPrefix);

                    $this->globalIdentityIdsByKey[$key] = $globalIdentityId;
                } else {
                    $stats['global_matched']++;
                    $this->appendNote($globalIdentityId, $localIdentity->note ?? null, $dryRun, $stats);
                    $this->appendAdminNote($globalIdentityId, $this->sourceTag($tenantPrefix, (int)$localIdentity->id), $dryRun, $stats);
                }

                if ($created && $dryRun) {
                    $this->countWouldAppendNewGlobalMetadata($tenantPrefix, (int)$localIdentity->id, $professionTable, $religionTable, $stats);
                } else {
                    $this->copyProfessions($globalIdentityId, (int)$localIdentity->id, $professionTable, $dryRun, $stats);
                    $this->copyReligions($globalIdentityId, (int)$localIdentity->id, $religionTable, $dryRun, $stats);
                }

                $this->linkLocalIdentity($identitiesTable, (int)$localIdentity->id, $globalIdentityId, $dryRun, $stats);
            }
        });

        return $stats;
    }

    private function loadGlobalIdentityKeys(): void
    {
        $this->globalIdentityIdsByKey = [];

        if (!Schema::hasTable('global_identities')) {
            return;
        }

        DB::table('global_identities')
            ->select(array_merge(['id'], self::DUPLICATE_FIELDS))
            ->orderBy('id')
            ->chunk(1000, function ($globalIdentities) {
                foreach ($globalIdentities as $globalIdentity) {
                    $key = $this->duplicateKey($globalIdentity);
                    $this->globalIdentityIdsByKey[$key] ??= (int)$globalIdentity->id;
                }
            });
    }

    private function createGlobalIdentity(object $localIdentity, string $tenantPrefix): int
    {
        $now = now();
        $data = collect(self::COPY_FIELDS)
            ->mapWithKeys(fn($field) => [$field => $localIdentity->{$field} ?? null])
            ->all();

        $data['created_at'] = $localIdentity->created_at ?? $now;
        $data['updated_at'] = $localIdentity->updated_at ?? $now;
        $data['admin_notes'] = $this->sourceTag($tenantPrefix, (int)$localIdentity->id);

        return (int)DB::table('global_identities')->insertGetId($data);
    }

    private function appendNote(int $globalIdentityId, ?string $note, bool $dryRun, array &$stats): void
    {
        $note = $this->normalizeTextValue($note);
        if ($note === null) {
            return;
        }

        if ($globalIdentityId < 1) {
            $stats['notes_updated']++;
            return;
        }

        $existing = DB::table('global_identities')->where('id', $globalIdentityId)->value('note');
        $notes = $this->splitNotes($existing);

        if (in_array($note, $notes, true)) {
            return;
        }

        if ($dryRun) {
            $stats['notes_updated']++;
            return;
        }

        $notes[] = $note;

        DB::table('global_identities')
            ->where('id', $globalIdentityId)
            ->update([
                'note' => implode(self::NOTE_SEPARATOR, $notes),
                'updated_at' => now(),
            ]);

        $stats['notes_updated']++;
    }

    private function appendAdminNote(int $globalIdentityId, string $sourceTag, bool $dryRun, array &$stats): void
    {
        if ($globalIdentityId < 1) {
            $stats['admin_notes_updated']++;
            return;
        }

        $existing = DB::table('global_identities')->where('id', $globalIdentityId)->value('admin_notes');
        $tags = $this->splitAdminNotes($existing);

        if (in_array($sourceTag, $tags, true)) {
            return;
        }

        if ($dryRun) {
            $stats['admin_notes_updated']++;
            return;
        }

        $tags[] = $sourceTag;

        DB::table('global_identities')
            ->where('id', $globalIdentityId)
            ->update([
                'admin_notes' => implode(', ', $tags),
                'updated_at' => now(),
            ]);

        $stats['admin_notes_updated']++;
    }

    private function copyProfessions(int $globalIdentityId, int $localIdentityId, string $professionTable, bool $dryRun, array &$stats): void
    {
        if (!Schema::hasTable($professionTable) || $globalIdentityId < 1) {
            return;
        }

        $professions = DB::table($professionTable)
            ->where('identity_id', $localIdentityId)
            ->whereNotNull('global_profession_id')
            ->select(['global_profession_id', 'position'])
            ->get();

        foreach ($professions as $profession) {
            $exists = DB::table('global_identity_profession')
                ->where('global_identity_id', $globalIdentityId)
                ->where('global_profession_id', $profession->global_profession_id)
                ->exists();

            if ($exists) {
                continue;
            }

            if (!$dryRun) {
                DB::table('global_identity_profession')->insert([
                    'global_identity_id' => $globalIdentityId,
                    'global_profession_id' => $profession->global_profession_id,
                    'position' => $profession->position,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $stats['professions_inserted']++;
        }
    }

    private function copyReligions(int $globalIdentityId, int $localIdentityId, string $religionTable, bool $dryRun, array &$stats): void
    {
        if (!Schema::hasTable($religionTable) || $globalIdentityId < 1) {
            return;
        }

        $religionIds = DB::table($religionTable)
            ->where('identity_id', $localIdentityId)
            ->pluck('religion_id');

        foreach ($religionIds as $religionId) {
            $exists = DB::table('global_identity_religion')
                ->where('global_identity_id', $globalIdentityId)
                ->where('religion_id', $religionId)
                ->exists();

            if ($exists) {
                continue;
            }

            if (!$dryRun) {
                DB::table('global_identity_religion')->insert([
                    'global_identity_id' => $globalIdentityId,
                    'religion_id' => $religionId,
                ]);
            }

            $stats['religions_inserted']++;
        }
    }

    private function countWouldAppendNewGlobalMetadata(
        string $tenantPrefix,
        int $localIdentityId,
        string $professionTable,
        string $religionTable,
        array &$stats
    ): void {
        if (Schema::hasTable($professionTable)) {
            $stats['professions_inserted'] += DB::table($professionTable)
                ->where('identity_id', $localIdentityId)
                ->whereNotNull('global_profession_id')
                ->distinct()
                ->count('global_profession_id');
        }

        if (Schema::hasTable($religionTable)) {
            $stats['religions_inserted'] += DB::table($religionTable)
                ->where('identity_id', $localIdentityId)
                ->distinct()
                ->count('religion_id');
        }

        $stats['admin_notes_updated'] += 0;
    }

    private function linkLocalIdentity(string $identitiesTable, int $localIdentityId, int $globalIdentityId, bool $dryRun, array &$stats): void
    {
        if ($globalIdentityId < 1) {
            $stats['local_links_updated']++;
            return;
        }

        $current = DB::table($identitiesTable)->where('id', $localIdentityId)->value('global_identity_id');
        if ((int)$current === $globalIdentityId) {
            return;
        }

        if (!$dryRun) {
            DB::table($identitiesTable)
                ->where('id', $localIdentityId)
                ->update([
                    'global_identity_id' => $globalIdentityId,
                    'updated_at' => now(),
                ]);
        }

        $stats['local_links_updated']++;
    }

    private function normalizeTextValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function splitNotes(?string $note): array
    {
        $note = $this->normalizeTextValue($note);
        if ($note === null) {
            return [];
        }

        return collect(explode(self::NOTE_SEPARATOR, $note))
            ->map(fn($part) => trim($part))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function splitAdminNotes(?string $adminNotes): array
    {
        return collect(explode(',', (string)$adminNotes))
            ->map(fn($part) => trim($part))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function sourceTag(string $tenantPrefix, int $localIdentityId): string
    {
        return "{$tenantPrefix}#{$localIdentityId}";
    }

    private function emptyStats(): array
    {
        return [
            'tenants_total' => 0,
            'tenants_processed' => 0,
            'missing_identity_table' => 0,
            'local_seen' => 0,
            'global_created' => 0,
            'global_matched' => 0,
            'notes_updated' => 0,
            'admin_notes_updated' => 0,
            'professions_inserted' => 0,
            'religions_inserted' => 0,
            'local_links_updated' => 0,
            'tenant_results' => [],
        ];
    }
}
