<?php

namespace App\Services;

use App\Models\MergeAuditLog;
use App\Models\Profession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocalProfessionMergeService
{
    public function findCandidates(array $criteria = [], array $options = []): Collection
    {
        if (!tenancy()->initialized) {
            return collect();
        }

        $locale = app()->getLocale() === 'en' ? 'en' : 'cs';
        $threshold = (int)($options['name_similarity_threshold'] ?? 80);
        $enforceCategory = in_array('same_category', $criteria, true);

        $professions = Profession::query()
            ->select(['id', 'name', 'profession_category_id', 'created_at'])
            ->with('profession_category:id,name')
            ->withCount('identities')
            ->orderBy('created_at', 'asc')
            ->get();

        $professions->each(function (Profession $profession) use ($locale): void {
            $profession->name_locale = $this->normalize($profession->getTranslation('name', $locale));
            $profession->cs = trim((string)$profession->getTranslation('name', 'cs'));
            $profession->en = trim((string)$profession->getTranslation('name', 'en'));
        });

        $connections = [];
        $link = function (int $id1, int $id2) use (&$connections): void {
            $connections[$id1][] = $id2;
            $connections[$id2][] = $id1;
        };

        $items = $professions->values()->all();
        $count = count($items);

        if (in_array('name_similarity', $criteria, true)) {
            if ($threshold === 0) {
                if ($enforceCategory) {
                    $byCategory = $professions->groupBy('profession_category_id');
                    foreach ($byCategory as $group) {
                        $groupItems = $group->values()->all();
                        $groupCount = count($groupItems);
                        for ($i = 1; $i < $groupCount; $i++) {
                            $link($groupItems[0]->id, $groupItems[$i]->id);
                        }
                    }
                } else {
                    for ($i = 1; $i < $count; $i++) {
                        $link($items[0]->id, $items[$i]->id);
                    }
                }
            } else {
                for ($i = 0; $i < $count; $i++) {
                    if (strlen($items[$i]->name_locale) < 2) {
                        continue;
                    }

                    for ($j = $i + 1; $j < $count; $j++) {
                        if ($enforceCategory && $items[$i]->profession_category_id !== $items[$j]->profession_category_id) {
                            continue;
                        }

                        if ($items[$i]->name_locale === '' || $items[$j]->name_locale === '') {
                            continue;
                        }

                        if ($items[$i]->name_locale[0] !== $items[$j]->name_locale[0]) {
                            continue;
                        }

                        $pct = 0.0;
                        similar_text($items[$i]->name_locale, $items[$j]->name_locale, $pct);

                        if ($pct >= $threshold) {
                            $link($items[$i]->id, $items[$j]->id);
                        }
                    }
                }
            }
        }

        $clusters = collect();
        $visited = [];

        foreach ($professions as $profession) {
            if (isset($visited[$profession->id]) || !isset($connections[$profession->id])) {
                continue;
            }

            $clusterIds = [$profession->id];
            $queue = [$profession->id];
            $visited[$profession->id] = true;

            while (!empty($queue)) {
                $current = array_pop($queue);
                foreach ($connections[$current] ?? [] as $neighbor) {
                    if (!isset($visited[$neighbor])) {
                        $visited[$neighbor] = true;
                        $clusterIds[] = $neighbor;
                        $queue[] = $neighbor;
                    }
                }
            }

            if (count($clusterIds) > 1) {
                $reason = __('hiko.merge_by_name_similarity');
                if ($threshold === 0) {
                    $reason .= ' (All)';
                } elseif ($enforceCategory) {
                    $reason .= ' + ' . __('hiko.same_category');
                } else {
                    $reason .= " ({$threshold}%)";
                }

                $clusters->push([
                    'reason' => $reason,
                    'items' => $professions->whereIn('id', $clusterIds)->sortBy('created_at')->values(),
                ]);
            }
        }

        return $clusters->map(function (array $cluster, int $index): array {
            return [
                'id' => $index,
                'reason' => $cluster['reason'],
                'items' => $cluster['items']->map(function (Profession $profession): array {
                    $category = $profession->profession_category;

                    return [
                        'id' => $profession->id,
                        'cs' => $profession->cs,
                        'en' => $profession->en,
                        'profession_category_id' => $profession->profession_category_id,
                        'profession_category_label' => $category ? $category->getTranslation('name', app()->getLocale()) : '—',
                        'identities_count' => $profession->identities_count,
                        'created_at' => $profession->created_at,
                    ];
                })->values()->toArray(),
            ];
        })->values();
    }

    public function merge(array $data): array
    {
        $result = [];
        $payload = $data;

        try {
            DB::transaction(function () use ($data, &$result): void {
                $tenantPrefix = tenancy()->tenant->table_prefix;
                $pivotTable = "{$tenantPrefix}__identity_profession";

                $targetId = (int)$data['target_id'];
                $sourceIds = array_map('intval', $data['source_ids']);
                $selectedIds = array_values(array_unique(array_merge([$targetId], $sourceIds)));

                $selectedCategoryIds = Profession::query()
                    ->whereIn('id', $selectedIds)
                    ->pluck('profession_category_id');

                if ($selectedCategoryIds->filter()->isEmpty()) {
                    throw new \RuntimeException(__('hiko.local_profession_merge_requires_category'));
                }

                $target = Profession::query()->findOrFail($targetId);

                $target->update([
                    'name' => [
                        'cs' => trim((string)($data['attributes']['cs'] ?? '')),
                        'en' => trim((string)($data['attributes']['en'] ?? '')),
                    ],
                    'profession_category_id' => (int)$data['attributes']['profession_category_id'],
                ]);

                $processed = 0;
                foreach ($sourceIds as $sourceId) {
                    if ($sourceId === $targetId) {
                        continue;
                    }

                    $links = DB::table($pivotTable)
                        ->where('profession_id', $sourceId)
                        ->get();

                    foreach ($links as $link) {
                        $alreadyLinked = DB::table($pivotTable)
                            ->where('identity_id', $link->identity_id)
                            ->where('profession_id', $targetId)
                            ->exists();

                        if ($alreadyLinked) {
                            DB::table($pivotTable)
                                ->where('identity_id', $link->identity_id)
                                ->where('profession_id', $sourceId)
                                ->delete();

                            continue;
                        }

                        DB::table($pivotTable)
                            ->where('identity_id', $link->identity_id)
                            ->where('profession_id', $sourceId)
                            ->update(['profession_id' => $targetId]);
                    }

                    Profession::query()->where('id', $sourceId)->delete();
                    $processed++;
                }

                $result = [
                    'target_id' => $targetId,
                    'merged_count' => $processed,
                    'source_ids' => $sourceIds,
                ];
            });

            $this->logAudit('success', $payload, $result);
            Log::info('[LocalProfessionMerge] success', $result);

            return $result;
        } catch (\Throwable $e) {
            $this->logAudit('error', $payload, [], $e->getMessage());
            Log::error('[LocalProfessionMerge] error: ' . $e->getMessage(), ['payload' => $payload]);
            throw $e;
        }
    }

    private function normalize(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $normalized = trim(mb_strtolower($value));

        if (function_exists('removeAccents')) {
            $normalized = removeAccents($normalized);
        }

        return $normalized;
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
                'entity' => 'profession',
                'operation' => 'local_merge',
                'status' => $status,
                'payload' => $payload,
                'result' => $result,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            Log::error('[LocalProfessionMerge] failed to persist audit log: ' . $e->getMessage());
        }
    }
}
