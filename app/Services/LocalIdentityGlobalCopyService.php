<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LocalIdentityGlobalCopyService
{
    public const NOTE_SEPARATOR = "\n\n===\n\n";

    private const MATCH_NAME_FIELDS = [
        'surname',
        'forename',
        'type',
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

    private array $globalIdentityCandidatesByNameKey = [];

    private int $nextDryRunId = -1;

    public function run(array $options = []): array
    {
        $dryRun = (bool)($options['dry_run'] ?? false);
        $tenantPrefixes = $this->tenantPrefixes($options['tenants'] ?? []);
        $chunkSize = max(1, (int)($options['chunk'] ?? 500));

        $this->nextDryRunId = -1;
        $this->loadGlobalIdentityCandidates();

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

    public function reset(array $options = []): array
    {
        $dryRun = (bool)($options['dry_run'] ?? false);
        $type = $this->normalizeResetType($options['type'] ?? null);
        $globalIdentityIds = Schema::hasTable('global_identities')
            ? DB::table('global_identities')
                ->when($type !== null, fn($query) => $query->where('type', $type))
                ->pluck('id')
                ->map(fn($id) => (int)$id)
                ->all()
            : [];

        return array_merge(
            ['type' => $type],
            $this->deleteGlobalIdentities($globalIdentityIds, $dryRun)
        );
    }

    public function removeUndatedDuplicateGroups(array $options = []): array
    {
        $dryRun = (bool)($options['dry_run'] ?? false);
        $groups = Schema::hasTable('global_identities')
            ? DB::table('global_identities')
                ->select(['id', 'name', 'type', 'birth_year', 'death_year'])
                ->orderBy('id')
                ->get()
                ->groupBy(fn($identity) => json_encode([
                    'name' => $identity->name,
                    'type' => $identity->type,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            : collect();

        $fullyUndatedGroups = $groups->filter(function ($group) {
            return $group->count() > 1
                && $group->every(fn($identity) => $this->isBlankDate($identity->birth_year)
                    && $this->isBlankDate($identity->death_year));
        });
        $uniqueUndatedRecords = $groups
            ->filter(fn($group) => $group->count() === 1)
            ->flatten(1)
            ->filter(fn($identity) => $this->isBlankDate($identity->birth_year)
                && $this->isBlankDate($identity->death_year));

        $globalIdentityIds = $fullyUndatedGroups
            ->flatten(1)
            ->pluck('id')
            ->merge($uniqueUndatedRecords->pluck('id'))
            ->map(fn($id) => (int)$id)
            ->unique()
            ->values()
            ->all();

        return array_merge(
            [
                'duplicate_groups' => $fullyUndatedGroups->count(),
                'unique_undated_records' => $uniqueUndatedRecords->count(),
            ],
            $this->deleteGlobalIdentities($globalIdentityIds, $dryRun)
        );
    }

    public function removeAllUndatedGlobalIdentities(array $options = []): array
    {
        $dryRun = (bool)($options['dry_run'] ?? false);
        $globalIdentityIds = Schema::hasTable('global_identities')
            ? DB::table('global_identities')
                ->select(['id', 'birth_year', 'death_year'])
                ->orderBy('id')
                ->get()
                ->filter(fn($identity) => $this->isBlankDate($identity->birth_year)
                    && $this->isBlankDate($identity->death_year))
                ->pluck('id')
                ->map(fn($id) => (int)$id)
                ->values()
                ->all()
            : [];

        return array_merge(
            ['strict' => true],
            $this->deleteGlobalIdentities($globalIdentityIds, $dryRun)
        );
    }

    private function deleteGlobalIdentities(array $globalIdentityIds, bool $dryRun): array
    {
        $tenantPrefixes = $this->tenantPrefixes();
        $stats = [
            'dry_run' => $dryRun,
            'local_identity_links' => 0,
            'identity_letter_links' => 0,
            'global_identity_professions' => 0,
            'global_identity_religions' => 0,
            'global_identity_keywords' => 0,
            'global_identities' => count($globalIdentityIds),
        ];

        if ($globalIdentityIds !== []) {
            foreach ($tenantPrefixes as $tenantPrefix) {
                $identitiesTable = "{$tenantPrefix}__identities";
                $identityLetterTable = "{$tenantPrefix}__identity_letter";

                if (Schema::hasTable($identitiesTable) && Schema::hasColumn($identitiesTable, 'global_identity_id')) {
                    $stats['local_identity_links'] += DB::table($identitiesTable)
                        ->whereIn('global_identity_id', $globalIdentityIds)
                        ->count();
                }

                if (Schema::hasTable($identityLetterTable) && Schema::hasColumn($identityLetterTable, 'global_identity_id')) {
                    $stats['identity_letter_links'] += DB::table($identityLetterTable)
                        ->whereIn('global_identity_id', $globalIdentityIds)
                        ->count();
                }
            }

            foreach ([
                'global_identity_profession' => ['global_identity_professions', 'global_identity_id'],
                'global_identity_religion' => ['global_identity_religions', 'global_identity_id'],
                'global_identity_keyword' => ['global_identity_keywords', 'identity_id'],
            ] as $table => [$stat, $foreignKey]) {
                if (Schema::hasTable($table)) {
                    $stats[$stat] = DB::table($table)
                        ->whereIn($foreignKey, $globalIdentityIds)
                        ->count();
                }
            }
        }

        if (!$dryRun && $globalIdentityIds !== []) {
            DB::transaction(function () use ($tenantPrefixes, $globalIdentityIds) {
                foreach ($tenantPrefixes as $tenantPrefix) {
                    $identitiesTable = "{$tenantPrefix}__identities";
                    $identityLetterTable = "{$tenantPrefix}__identity_letter";

                    if (Schema::hasTable($identitiesTable) && Schema::hasColumn($identitiesTable, 'global_identity_id')) {
                        DB::table($identitiesTable)
                            ->whereIn('global_identity_id', $globalIdentityIds)
                            ->update(['global_identity_id' => null]);
                    }

                    if (Schema::hasTable($identityLetterTable) && Schema::hasColumn($identityLetterTable, 'global_identity_id')) {
                        DB::table($identityLetterTable)
                            ->whereIn('global_identity_id', $globalIdentityIds)
                            ->update(['global_identity_id' => null]);
                    }
                }

                foreach ([
                    'global_identity_keyword' => 'identity_id',
                    'global_identity_profession' => 'global_identity_id',
                    'global_identity_religion' => 'global_identity_id',
                ] as $table => $foreignKey) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->whereIn($foreignKey, $globalIdentityIds)->delete();
                    }
                }

                if (Schema::hasTable('global_identities')) {
                    DB::table('global_identities')->whereIn('id', $globalIdentityIds)->delete();
                }
            });
        }

        $this->globalIdentityCandidatesByNameKey = [];

        return $stats;
    }

    private function isBlankDate(mixed $value): bool
    {
        $value = trim((string)$value);

        return $value === '' || $value === '0';
    }

    private function normalizeResetType(mixed $type): ?string
    {
        $type = trim((string)$type);
        if ($type === '') {
            return null;
        }

        if (!in_array($type, ['person', 'institution'], true)) {
            throw new \InvalidArgumentException('Reset type must be person or institution.');
        }

        return $type;
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
            collect(self::MATCH_NAME_FIELDS)
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

        return $trimmed;
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

                if (($localIdentity->type ?? null) !== 'person') {
                    $stats['local_skipped_non_person']++;
                    continue;
                }

                if (($localIdentity->global_identity_id ?? null) !== null) {
                    $stats['local_already_linked']++;
                    continue;
                }

                $eligibleForMatching = $this->isEligibleForMatching($localIdentity);
                if (!$eligibleForMatching) {
                    $stats['local_incomplete_match_data']++;
                }

                $globalIdentityId = $eligibleForMatching
                    ? $this->findCompatibleGlobalIdentityId($localIdentity, $stats)
                    : null;
                $created = false;

                if ($globalIdentityId === null) {
                    $created = true;
                    $stats['global_created']++;
                    $globalIdentityId = $dryRun
                        ? $this->nextDryRunId--
                        : $this->createGlobalIdentity($localIdentity, $tenantPrefix);

                    $this->addGlobalIdentityCandidate($globalIdentityId, $localIdentity);
                } else {
                    $stats['global_matched']++;
                    $this->mergeMatchedMetadata($globalIdentityId, $localIdentity, $dryRun, $stats);
                    $this->appendNote(
                        $globalIdentityId,
                        $localIdentity->note ?? null,
                        $this->sourceTag($tenantPrefix, (int)$localIdentity->id),
                        $dryRun,
                        $stats
                    );
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

    private function loadGlobalIdentityCandidates(): void
    {
        $this->globalIdentityCandidatesByNameKey = [];

        if (!Schema::hasTable('global_identities')) {
            return;
        }

        DB::table('global_identities')
            ->select(array_merge(['id', 'birth_year', 'death_year'], self::MATCH_NAME_FIELDS))
            ->orderBy('id')
            ->chunk(1000, function ($globalIdentities) {
                foreach ($globalIdentities as $globalIdentity) {
                    if (!$this->isEligibleForMatching($globalIdentity)) {
                        continue;
                    }

                    $this->addGlobalIdentityCandidate((int)$globalIdentity->id, $globalIdentity);
                }
            });
    }

    private function createGlobalIdentity(object $localIdentity, string $tenantPrefix): int
    {
        $now = now();
        $data = collect(self::COPY_FIELDS)
            ->mapWithKeys(fn($field) => [$field => $localIdentity->{$field} ?? null])
            ->all();

        $data['gender'] = $this->normalizeGender($data['gender'] ?? null);
        $data['birth_year'] = $this->normalizeForDuplicateKey($data['birth_year'] ?? null);
        $data['death_year'] = $this->normalizeForDuplicateKey($data['death_year'] ?? null);
        $data['note'] = $this->formatSourceNote(
            $this->sourceTag($tenantPrefix, (int)$localIdentity->id),
            $data['note'] ?? null
        );
        $data['created_at'] = $localIdentity->created_at ?? $now;
        $data['updated_at'] = $localIdentity->updated_at ?? $now;
        $data['admin_notes'] = $this->sourceTag($tenantPrefix, (int)$localIdentity->id);

        return (int)DB::table('global_identities')->insertGetId($data);
    }

    private function appendNote(
        int $globalIdentityId,
        ?string $note,
        string $sourceTag,
        bool $dryRun,
        array &$stats
    ): void
    {
        $note = $this->formatSourceNote($sourceTag, $note);
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

    private function mergeMatchedMetadata(int $globalIdentityId, object $localIdentity, bool $dryRun, array &$stats): void
    {
        $this->enrichGlobalIdentityCandidateDates($globalIdentityId, $localIdentity);

        if ($globalIdentityId < 1) {
            $stats['metadata_updated']++;
            return;
        }

        $globalIdentity = DB::table('global_identities')->where('id', $globalIdentityId)->first();
        if (!$globalIdentity) {
            return;
        }

        $updates = [];
        $updates['general_name_modifier'] = $this->mergeDelimitedValues(
            $globalIdentity->general_name_modifier ?? null,
            $localIdentity->general_name_modifier ?? null,
            '; '
        );
        $updates['nationality'] = $this->mergeDelimitedValues(
            $globalIdentity->nationality ?? null,
            $localIdentity->nationality ?? null,
            ', ',
            '/[,|;]+/'
        );
        $updates['related_names'] = $this->mergeJsonArrays(
            $globalIdentity->related_names ?? null,
            $localIdentity->related_names ?? null
        );
        $updates['related_identity_resources'] = $this->mergeJsonArrays(
            $globalIdentity->related_identity_resources ?? null,
            $localIdentity->related_identity_resources ?? null
        );
        $updates['birth_year'] = $this->fillMissingValue(
            $globalIdentity->birth_year ?? null,
            $localIdentity->birth_year ?? null
        );
        $updates['death_year'] = $this->fillMissingValue(
            $globalIdentity->death_year ?? null,
            $localIdentity->death_year ?? null
        );

        $existingGender = $this->normalizeGender($globalIdentity->gender ?? null);
        $localGender = $this->normalizeGender($localIdentity->gender ?? null);
        if ($existingGender === null && $localGender !== null) {
            $updates['gender'] = $localGender;
        } elseif ($existingGender !== null && $localGender !== null && $existingGender !== $localGender) {
            $stats['gender_conflicts']++;
        } elseif (($globalIdentity->gender ?? null) !== $existingGender) {
            $updates['gender'] = $existingGender;
        }

        $updates = collect($updates)
            ->filter(fn($value, $field) => $value !== ($globalIdentity->{$field} ?? null))
            ->all();

        if ($updates === []) {
            return;
        }

        if (!$dryRun) {
            $updates['updated_at'] = now();
            DB::table('global_identities')->where('id', $globalIdentityId)->update($updates);
        }

        $stats['metadata_updated']++;
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

    private function isEligibleForMatching(object|array $identity): bool
    {
        $identity = (array)$identity;

        return ($identity['type'] ?? null) === 'person'
            && $this->normalizeForDuplicateKey($identity['surname'] ?? null) !== null
            && (
                $this->normalizeForDuplicateKey($identity['birth_year'] ?? null) !== null
                || $this->normalizeForDuplicateKey($identity['death_year'] ?? null) !== null
            );
    }

    private function findCompatibleGlobalIdentityId(object $identity, array &$stats): ?int
    {
        $nameKey = $this->duplicateKey($identity);
        $matches = collect($this->globalIdentityCandidatesByNameKey[$nameKey] ?? [])
            ->mapWithKeys(function (array $candidate, int $id) use ($identity) {
                $score = $this->dateCompatibilityScore($candidate, $identity);

                return $score > 0 ? [$id => $score] : [];
            });

        if ($matches->isEmpty()) {
            return null;
        }

        $bestScore = $matches->max();
        $bestIds = $matches->filter(fn(int $score) => $score === $bestScore)->keys();

        if ($bestIds->count() !== 1) {
            $stats['ambiguous_date_matches']++;
            return null;
        }

        return (int)$bestIds->first();
    }

    private function dateCompatibilityScore(object|array $left, object|array $right): int
    {
        $left = (array)$left;
        $right = (array)$right;
        $matchingKnownDates = 0;

        foreach (['birth_year', 'death_year'] as $field) {
            $leftValue = $this->normalizeForDuplicateKey($left[$field] ?? null);
            $rightValue = $this->normalizeForDuplicateKey($right[$field] ?? null);

            if ($leftValue !== null && $rightValue !== null) {
                if ($leftValue !== $rightValue) {
                    return 0;
                }

                $matchingKnownDates++;
            }
        }

        return $matchingKnownDates;
    }

    private function addGlobalIdentityCandidate(int $globalIdentityId, object|array $identity): void
    {
        if (!$this->isEligibleForMatching($identity)) {
            return;
        }

        $identity = (array)$identity;
        $this->globalIdentityCandidatesByNameKey[$this->duplicateKey($identity)][$globalIdentityId] = [
            'birth_year' => $this->normalizeForDuplicateKey($identity['birth_year'] ?? null),
            'death_year' => $this->normalizeForDuplicateKey($identity['death_year'] ?? null),
        ];
    }

    private function enrichGlobalIdentityCandidateDates(int $globalIdentityId, object|array $identity): void
    {
        $nameKey = $this->duplicateKey($identity);
        if (!isset($this->globalIdentityCandidatesByNameKey[$nameKey][$globalIdentityId])) {
            return;
        }

        foreach (['birth_year', 'death_year'] as $field) {
            $current = $this->globalIdentityCandidatesByNameKey[$nameKey][$globalIdentityId][$field] ?? null;
            $incoming = $this->normalizeForDuplicateKey(((array)$identity)[$field] ?? null);

            if ($current === null && $incoming !== null) {
                $this->globalIdentityCandidatesByNameKey[$nameKey][$globalIdentityId][$field] = $incoming;
            }
        }
    }

    private function fillMissingValue(?string $existing, ?string $incoming): ?string
    {
        return $this->normalizeForDuplicateKey($existing)
            ?? $this->normalizeForDuplicateKey($incoming);
    }

    private function normalizeGender(?string $gender): ?string
    {
        $gender = $this->normalizeTextValue($gender);
        if ($gender === null) {
            return null;
        }

        return match (mb_strtolower($gender)) {
            'm', 'male', 'muž', 'muz' => 'M',
            'f', 'female', 'žena', 'zena' => 'F',
            default => $gender,
        };
    }

    private function formatSourceNote(string $sourceTag, ?string $note): ?string
    {
        $note = $this->normalizeTextValue($note);

        return $note === null ? null : "[{$sourceTag}]: {$note}";
    }

    private function mergeDelimitedValues(
        ?string $existing,
        ?string $incoming,
        string $outputSeparator,
        string $splitPattern = '/[;]+/'
    ): ?string {
        $values = [];
        $seen = [];

        foreach ([$existing, $incoming] as $value) {
            foreach (preg_split($splitPattern, (string)$value) ?: [] as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }

                $key = mb_strtolower($part);
                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $values[] = $part;
            }
        }

        return $values === [] ? null : implode($outputSeparator, $values);
    }

    private function mergeJsonArrays(mixed $existing, mixed $incoming): ?string
    {
        $items = [];
        $seen = [];

        foreach ([$existing, $incoming] as $value) {
            if (is_string($value)) {
                $value = json_decode($value, true);
            }

            if (!is_array($value)) {
                continue;
            }

            foreach ($value as $item) {
                $key = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($key === false || isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $items[] = $item;
            }
        }

        return $items === []
            ? null
            : json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
            'local_skipped_non_person' => 0,
            'local_already_linked' => 0,
            'local_incomplete_match_data' => 0,
            'global_created' => 0,
            'global_matched' => 0,
            'metadata_updated' => 0,
            'gender_conflicts' => 0,
            'ambiguous_date_matches' => 0,
            'notes_updated' => 0,
            'admin_notes_updated' => 0,
            'professions_inserted' => 0,
            'religions_inserted' => 0,
            'local_links_updated' => 0,
            'tenant_results' => [],
        ];
    }
}
