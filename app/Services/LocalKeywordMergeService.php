<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\MergeAuditLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocalKeywordMergeService
{
    public function findCandidates(array $criteria = [], array $options = []): Collection
    {
        if (!tenancy()->initialized) {
            return collect();
        }

        $locale = app()->getLocale() === 'en' ? 'en' : 'cs';
        $threshold = (int)($options['name_similarity_threshold'] ?? 80);
        $enforceCategory = in_array('same_category', $criteria, true);

        $keywords = Keyword::query()
            ->select(['id', 'name', 'keyword_category_id', 'created_at'])
            ->with('keyword_category:id,name')
            ->withCount('letters')
            ->orderBy('created_at', 'asc')
            ->get();

        $keywords->each(function (Keyword $keyword) use ($locale): void {
            $keyword->name_locale = $this->normalize($keyword->getTranslation('name', $locale));
            $keyword->cs = trim((string)$keyword->getTranslation('name', 'cs'));
            $keyword->en = trim((string)$keyword->getTranslation('name', 'en'));
        });

        $connections = [];
        $link = function (int $id1, int $id2) use (&$connections): void {
            $connections[$id1][] = $id2;
            $connections[$id2][] = $id1;
        };

        $items = $keywords->values()->all();
        $count = count($items);

        if (in_array('name_similarity', $criteria, true)) {
            if ($threshold === 0) {
                if ($enforceCategory) {
                    $byCategory = $keywords->groupBy('keyword_category_id');
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
                        if ($enforceCategory && $items[$i]->keyword_category_id !== $items[$j]->keyword_category_id) {
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

        foreach ($keywords as $keyword) {
            if (isset($visited[$keyword->id]) || !isset($connections[$keyword->id])) {
                continue;
            }

            $clusterIds = [$keyword->id];
            $queue = [$keyword->id];
            $visited[$keyword->id] = true;

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
                    'items' => $keywords->whereIn('id', $clusterIds)->sortBy('created_at')->values(),
                ]);
            }
        }

        return $clusters->map(function (array $cluster, int $index): array {
            return [
                'id' => $index,
                'reason' => $cluster['reason'],
                'items' => $cluster['items']->map(function (Keyword $keyword): array {
                    $category = $keyword->keyword_category;

                    return [
                        'id' => $keyword->id,
                        'cs' => $keyword->cs,
                        'en' => $keyword->en,
                        'keyword_category_id' => $keyword->keyword_category_id,
                        'keyword_category_label' => $category ? $category->getTranslation('name', app()->getLocale()) : '—',
                        'letters_count' => $keyword->letters_count,
                        'created_at' => $keyword->created_at,
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
                $pivotTable = "{$tenantPrefix}__keyword_letter";

                $targetId = (int)$data['target_id'];
                $sourceIds = array_map('intval', $data['source_ids']);

                $target = Keyword::query()->findOrFail($targetId);

                $target->update([
                    'name' => [
                        'cs' => trim((string)($data['attributes']['cs'] ?? '')),
                        'en' => trim((string)($data['attributes']['en'] ?? '')),
                    ],
                    'keyword_category_id' => $data['attributes']['keyword_category_id'] ?? null,
                ]);

                $processed = 0;
                foreach ($sourceIds as $sourceId) {
                    if ($sourceId === $targetId) {
                        continue;
                    }

                    $links = DB::table($pivotTable)
                        ->where('keyword_id', $sourceId)
                        ->get();

                    foreach ($links as $link) {
                        $alreadyLinked = DB::table($pivotTable)
                            ->where('letter_id', $link->letter_id)
                            ->where('keyword_id', $targetId)
                            ->exists();

                        if ($alreadyLinked) {
                            DB::table($pivotTable)
                                ->where('letter_id', $link->letter_id)
                                ->where('keyword_id', $sourceId)
                                ->delete();

                            continue;
                        }

                        DB::table($pivotTable)
                            ->where('letter_id', $link->letter_id)
                            ->where('keyword_id', $sourceId)
                            ->update(['keyword_id' => $targetId]);
                    }

                    Keyword::query()->where('id', $sourceId)->delete();
                    $processed++;
                }

                $result = [
                    'target_id' => $targetId,
                    'merged_count' => $processed,
                    'source_ids' => $sourceIds,
                ];
            });

            $this->logAudit('success', $payload, $result);
            Log::info('[LocalKeywordMerge] success', $result);

            return $result;
        } catch (\Throwable $e) {
            $this->logAudit('error', $payload, [], $e->getMessage());
            Log::error('[LocalKeywordMerge] error: ' . $e->getMessage(), ['payload' => $payload]);
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
                'entity' => 'keyword',
                'operation' => 'local_merge',
                'status' => $status,
                'payload' => $payload,
                'result' => $result,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            Log::error('[LocalKeywordMerge] failed to persist audit log: ' . $e->getMessage());
        }
    }
}
