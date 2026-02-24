<?php

namespace App\Services;

use App\Models\Identity;
use App\Models\MergeAuditLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocalIdentityMergeService
{
    /**
     * Find duplicate candidates.
     */
    public function findCandidates(array $criteria, array $options = []): Collection
    {
        if (!tenancy()->initialized) return collect();

        // 1. Fetch Identities with Relations needed for the UI
        $identities = Identity::query()
            ->with(['professions', 'globalProfessions', 'religions'])
            ->select(['id', 'name', 'surname', 'forename', 'birth_year', 'death_year', 'viaf_id', 'created_at', 'type', 'nationality', 'gender'])
            ->orderBy('created_at', 'asc')
            ->get();

        // 2. Pre-fetch Religion Translations for the current locale
        $locale = app()->getLocale();
        $allReligionIds = $identities->pluck('religions')->flatten()->pluck('id')->unique()->toArray();

        $religionMap = [];
        if (!empty($allReligionIds)) {
            $religionMap = DB::table('religion_translations')
                ->whereIn('religion_id', $allReligionIds)
                ->where('locale', $locale)
                ->pluck('path_text', 'religion_id')
                ->toArray();
        }

        // 3. Prepare formatter for UI display & Normalize names
        $identities->each(function ($identity) use ($religionMap, $locale) {
            // Normalize name for comparison logic
            $identity->normalized_name = $this->normalizeName($identity->name);

            // Format Religions for UI
            $identity->religions_list = $identity->religions->map(function ($r) use ($religionMap) {
                return $religionMap[$r->id] ?? $r->name;
            })->toArray();

            // Format Professions for UI
            $local = $identity->professions->toBase()->map(function ($p) use ($locale) {
                $name = json_decode($p->name, true)[$locale] ?? $p->name;
                return $name . ' (L)';
            });
            $global = $identity->globalProfessions->toBase()->map(function ($p) use ($locale) {
                $name = $p->getTranslation('name', $locale);
                return $name . ' (G)';
            });
            $identity->professions_list = $local->merge($global)->toArray();
        });

        // 4. Grouping and Linking Logic
        $connections = [];
        $link = function ($id1, $id2) use (&$connections) {
            $connections[$id1][] = $id2;
            $connections[$id2][] = $id1;
        };

        // --- VIAF ID ---
        if (in_array('viaf_id', $criteria)) {
            $identities
                ->whereNotNull('viaf_id')
                ->where('viaf_id', '!=', '')
                ->groupBy('viaf_id')
                ->filter(fn($g) => $g->count() > 1)
                ->each(function ($g) use ($link) {
                    $f = $g->first();
                    foreach ($g as $i) if ($i->id !== $f->id) $link($f->id, $i->id);
                });
        }

        // --- Dates ---
        if (in_array('dates', $criteria)) {
            $identities
                ->filter(function ($i) {
                    // STRICT CHECK: Exclude 0, '0', '', null, '????'
                    return $this->isValidDate($i->birth_year) && $this->isValidDate($i->death_year);
                })
                ->groupBy(fn($i) => $i->birth_year . '|' . $i->death_year)
                ->filter(fn($g) => $g->count() > 1)
                ->each(function ($g) use ($link) {
                    $f = $g->first();
                    foreach ($g as $i) if ($i->id !== $f->id) $link($f->id, $i->id);
                });
        }

        // --- Name Similarity ---
        if (in_array('name_similarity', $criteria)) {
            $threshold = $options['name_similarity_threshold'] ?? 80;
            $typeGroups = $identities->groupBy('type');

            foreach ($typeGroups as $items) {
                $items = $items->values()->all();
                $count = count($items);
                for ($i = 0; $i < $count; $i++) {
                    if (strlen($items[$i]->normalized_name) < 3) continue;

                    for ($j = $i + 1; $j < $count; $j++) {
                        // Optimization: Start letter must match
                        if ($items[$i]->normalized_name[0] !== $items[$j]->normalized_name[0]) continue;

                        $pct = 0.0;
                        similar_text($items[$i]->normalized_name, $items[$j]->normalized_name, $pct);

                        if ($pct >= $threshold) {
                            $link($items[$i]->id, $items[$j]->id);
                        }
                    }
                }
            }
        }

        // 5. Build Clusters
        $clusters = collect();
        $visited = [];

        foreach ($identities as $identity) {
            if (isset($visited[$identity->id]) || !isset($connections[$identity->id])) continue;

            $clusterIds = [$identity->id];
            $queue = [$identity->id];
            $visited[$identity->id] = true;

            while (!empty($queue)) {
                $curr = array_pop($queue);
                foreach ($connections[$curr] ?? [] as $n) {
                    if (!isset($visited[$n])) {
                        $visited[$n] = true;
                        $clusterIds[] = $n;
                        $queue[] = $n;
                    }
                }
            }

            if (count($clusterIds) > 1) {
                $clusters->push([
                    'reason' => 'Multiple Criteria',
                    'items' => $identities->whereIn('id', $clusterIds)->sortBy('created_at')->values()
                ]);
            }
        }

        return $clusters;
    }

    private function normalizeName(string $name): string
    {
        if (empty($name)) return '';
        return trim(strtolower(str_replace(',', '', removeAccents($name))));
    }

    private function isValidDate($date): bool
    {
        if (empty($date)) return false;
        if ($date === '0' || $date === 0) return false;
        if ($date === '????') return false;
        // Basic check for numeric content, allows "1900?" or "c. 1900" but filters strict zeros
        return preg_match('/[1-9]/', (string)$date) === 1;
    }

    /**
     * Execute the merge.
     */
    public function merge(array $data): void
    {
        $tenantPrefix = tenancy()->tenant->table_prefix;
        $auditPayload = $this->prepareAuditPayload($data);
        $auditResult = [];

        try {
            DB::transaction(function () use ($data, $tenantPrefix, &$auditResult) {
                $target = Identity::findOrFail($data['target_id']);

                // Collect all identities involved (Target + Sources)
                $allIds = array_merge([$data['target_id']], $data['source_ids']);
                $allIdentities = Identity::whereIn('id', $allIds)->get();

                // 1. Prepare Attributes for Target
                if ($data['attributes']['type'] === 'person') { // Person
                    $updateData = [
                        'name' => $data['attributes']['surname'] . ($data['attributes']['forename'] ? ", {$data['attributes']['forename']}" : ''),
                        'surname' => $data['attributes']['surname'],
                        'forename' => $data['attributes']['forename'],
                        'type' => $data['attributes']['type'],
                        'nationality' => $data['attributes']['nationality'],
                        'gender' => $data['attributes']['gender'],
                        'birth_year' => $data['attributes']['birth_year'],
                        'death_year' => $data['attributes']['death_year'],
                        'viaf_id' => $data['attributes']['viaf_id'],
                        'alternative_names' => [],
                    ];
                } else {    // Institution
                    $updateData = [
                        'name' => $data['attributes']['name'],
                        'surname' => null,
                        'forename' => null,
                        'type' => $data['attributes']['type'],
                        'nationality' => null,
                        'gender' => null,
                        'birth_year' => null,
                        'death_year' => null,
                        'viaf_id' => null,
                        'alternative_names' => [],
                    ];
                }

                // 2. Concatenate Logic (Related Names, Resources, Notes)
                $mergedRelatedNames = [];
                $mergedResources = [];
                $mergedNotes = [];
                $mergedGenNameModifiers = [];

                foreach ($allIdentities as $identity) {
                    // Related Names
                    if (!empty($identity->related_names) && is_array($identity->related_names)) {
                        foreach ($identity->related_names as $rn) {
                            $mergedRelatedNames[] = $rn;
                        }
                    }

                    // Resources
                    if (!empty($identity->related_identity_resources) && is_array($identity->related_identity_resources)) {
                        foreach ($identity->related_identity_resources as $rr) {
                            $mergedResources[] = $rr;
                        }
                    }

                    // Notes
                    if (!empty($identity->note)) {
                        $mergedNotes[] = trim($identity->note);
                    }

                    // General Name Modifiers
                    if (!empty($identity->general_name_modifier)) {
                        $mergedGenNameModifiers[] = trim($identity->general_name_modifier);
                    }
                }

                // Deduplicate Arrays
                $updateData['related_names'] = array_map('unserialize', array_unique(array_map('serialize', $mergedRelatedNames)));
                $updateData['related_identity_resources'] = array_map('unserialize', array_unique(array_map('serialize', $mergedResources)));

                // Join Notes & Modifiers
                $updateData['note'] = implode(' | ', array_unique($mergedNotes));
                $updateData['general_name_modifier'] = implode('; ', array_unique($mergedGenNameModifiers));

                // Update Target Main Record
                $target->update($updateData);

                // 3. Handle Religions (Select ONE source)
                $religionSourceId = $data['attributes']['selected_religion_source_id'] ?? null;
                if ($religionSourceId) {
                    $pivotReligion = "{$tenantPrefix}__identity_religion";
                    $religionIds = DB::table($pivotReligion)
                        ->where('identity_id', $religionSourceId)
                        ->pluck('religion_id')
                        ->toArray();

                    DB::table($pivotReligion)->where('identity_id', $target->id)->delete();

                    if (!empty($religionIds)) {
                        $target->religions()->attach($religionIds);
                    }
                }

                // 4. Handle Professions (Select ONE source)
                $professionSourceId = $data['attributes']['selected_profession_source_id'] ?? null;

                if ($professionSourceId) {
                    $pivotProf = "{$tenantPrefix}__identity_profession";

                    $sourceProfRecords = DB::table($pivotProf)
                        ->where('identity_id', $professionSourceId)
                        ->orderBy('position')
                        ->get();

                    DB::table($pivotProf)->where('identity_id', $target->id)->delete();

                    foreach ($sourceProfRecords as $rec) {
                        DB::table($pivotProf)->insert([
                            'identity_id' => $target->id,
                            'profession_id' => $rec->profession_id,
                            'global_profession_id' => $rec->global_profession_id,
                            'position' => $rec->position,
                        ]);
                    }
                }

                // 5. Merge Letters (Re-link all sources to target)
                $pivotLetter = "{$tenantPrefix}__identity_letter";
                foreach ($data['source_ids'] as $sourceId) {
                    if ((int)$sourceId === (int)$data['target_id']) {
                        continue;
                    }

                    $sourceLetters = DB::table($pivotLetter)->where('identity_id', $sourceId)->get();

                    foreach ($sourceLetters as $sl) {
                        $exists = DB::table($pivotLetter)
                            ->where('identity_id', $target->id)
                            ->where('letter_id', $sl->letter_id)
                            ->where('role', $sl->role)
                            ->exists();

                        if (!$exists) {
                            DB::table($pivotLetter)->where('id', $sl->id)->update(['identity_id' => $target->id]);
                        } else {
                            DB::table($pivotLetter)->where('id', $sl->id)->delete();
                        }
                    }

                    Identity::where('id', $sourceId)->delete();
                }

                $auditResult = [
                    'target_id' => (int)$data['target_id'],
                    'source_ids' => array_map('intval', $data['source_ids']),
                    'selected_religion_source_id' => isset($data['attributes']['selected_religion_source_id'])
                        ? (int)$data['attributes']['selected_religion_source_id']
                        : null,
                    'selected_profession_source_id' => isset($data['attributes']['selected_profession_source_id'])
                        ? (int)$data['attributes']['selected_profession_source_id']
                        : null,
                    'merged_count' => count($data['source_ids']),
                ];
            });

            $this->logAudit('success', $auditPayload, $auditResult);
            Log::info('[LocalIdentityMerge] success', $auditResult);
        } catch (\Throwable $e) {
            $this->logAudit('error', $auditPayload, $auditResult, $e->getMessage());
            Log::error('[LocalIdentityMerge] error: ' . $e->getMessage(), [
                'payload' => $auditPayload,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function prepareAuditPayload(array $data): array
    {
        $payload = $data;

        // Keep selected source IDs only for religions/professions.
        $payload['attributes']['selected_religion_source_id'] = $data['attributes']['selected_religion_source_id'] ?? null;
        $payload['attributes']['selected_profession_source_id'] = $data['attributes']['selected_profession_source_id'] ?? null;
        unset(
            $payload['attributes']['religions_list'],
            $payload['attributes']['professions_list'],
            $payload['attributes']['selected_religions'],
            $payload['attributes']['selected_professions']
        );

        // Truncate free-text fields to first 100 chars.
        $payload['attributes']['note'] = $this->truncateValue($payload['attributes']['note'] ?? null);
        $payload['attributes']['general_name_modifier'] = $this->truncateValue($payload['attributes']['general_name_modifier'] ?? null);
        $payload['attributes']['related_identity_resources'] = $this->truncateRecursive($payload['attributes']['related_identity_resources'] ?? null);
        $payload['attributes']['related_names'] = $this->truncateRecursive($payload['attributes']['related_names'] ?? null);
        $payload['attributes']['name'] = $this->truncateValue($payload['attributes']['name'] ?? null);
        $payload['attributes']['surname'] = $this->truncateValue($payload['attributes']['surname'] ?? null);
        $payload['attributes']['forename'] = $this->truncateValue($payload['attributes']['forename'] ?? null);

        return $payload;
    }

    private function truncateRecursive($value)
    {
        if (is_string($value)) {
            return $this->truncateValue($value);
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->truncateRecursive($v);
            }
        }

        return $value;
    }

    private function truncateValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = (string)$value;
        return mb_substr($text, 0, 100);
    }

    private function logAudit(string $status, array $payload, array $result = [], ?string $errorMessage = null): void
    {
        try {
            $user = auth()->user();

            MergeAuditLog::create([
                'tenant_id' => tenancy()->tenant?->id,
                'tenant_prefix' => tenancy()->tenant?->table_prefix,
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'entity' => 'identity',
                'operation' => 'local_merge',
                'status' => $status,
                'payload' => $payload,
                'result' => $result,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            Log::error('[LocalIdentityMerge] failed to persist audit log: ' . $e->getMessage());
        }
    }
}
