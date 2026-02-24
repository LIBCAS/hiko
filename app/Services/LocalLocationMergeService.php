<?php

namespace App\Services;

use App\Models\Location;
use App\Models\MergeAuditLog;
use App\Models\Manifestation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocalLocationMergeService
{
    /**
     * Find duplicate candidates based on criteria.
     */
    public function findCandidates(array $criteria = [], array $options = []): Collection
    {
        if (!tenancy()->initialized) return collect();

        // 1. Fetch Locations with Manifestation Counts
        // We fetch counts for all 3 possible roles to ensure accuracy
        $locations = Location::query()
            ->select(['id', 'name', 'type', 'created_at'])
            ->withCount([
                'manifestationsAsRepository',
                'manifestationsAsArchive',
                'manifestationsAsCollection'
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        // 2. Normalize names and Calculate Total Count
        $locations->each(function ($loc) {
            $loc->normalized_name = $this->normalizeName($loc->name);

            // Sum the counts to get total usage
            $loc->total_manifestations_count =
                $loc->manifestations_as_repository_count +
                $loc->manifestations_as_archive_count +
                $loc->manifestations_as_collection_count;
        });

        // 3. Connection Logic
        $connections = [];
        $link = function ($id1, $id2) use (&$connections) {
            $connections[$id1][] = $id2;
            $connections[$id2][] = $id1;
        };

        $threshold = (int)($options['name_similarity_threshold'] ?? 80);
        $enforceType = in_array('type', $criteria);

        // --- Criterion: Name Similarity ---
        if (in_array('name_similarity', $criteria)) {
            $items = $locations->values()->all();
            $count = count($items);

            // Special Case: Threshold 0 => Group EVERYTHING
            if ($threshold === 0) {
                if ($enforceType) {
                    // Group everything by Type
                    $byType = $locations->groupBy('type');
                    foreach ($byType as $typeGroup) {
                        $groupItems = $typeGroup->values()->all();
                        $groupCount = count($groupItems);
                        for ($i = 1; $i < $groupCount; $i++) {
                            $link($groupItems[0]->id, $groupItems[$i]->id);
                        }
                    }
                } else {
                    // Link EVERYTHING together
                    for ($i = 1; $i < $count; $i++) {
                        $link($items[0]->id, $items[$i]->id);
                    }
                }
            } else {
                // Standard Similarity Logic
                for ($i = 0; $i < $count; $i++) {
                    if (strlen($items[$i]->normalized_name) < 3) continue;

                    for ($j = $i + 1; $j < $count; $j++) {
                        // 1. Check Type Constraint
                        if ($enforceType && $items[$i]->type !== $items[$j]->type) {
                            continue;
                        }

                        // 2. Optimization: First char check
                        if ($items[$i]->normalized_name[0] !== $items[$j]->normalized_name[0]) {
                            continue;
                        }

                        // 3. Similarity Check
                        $pct = 0.0;
                        similar_text($items[$i]->normalized_name, $items[$j]->normalized_name, $pct);

                        if ($pct >= $threshold) {
                            $link($items[$i]->id, $items[$j]->id);
                        }
                    }
                }
            }
        }

        // 4. Build Clusters (Standard BFS)
        $clusters = collect();
        $visited = [];

        foreach ($locations as $loc) {
            if (isset($visited[$loc->id]) || !isset($connections[$loc->id])) continue;

            $clusterIds = [$loc->id];
            $queue = [$loc->id];
            $visited[$loc->id] = true;

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
                $reason = 'Name Similarity';
                if ($threshold === 0) $reason .= ' (All)';
                elseif ($enforceType) $reason .= ' + Type';
                else $reason .= " ({$threshold}%)";

                $clusters->push([
                    'reason' => $reason,
                    'items' => $locations->whereIn('id', $clusterIds)->sortBy('created_at')->values()
                ]);
            }
        }

        // 5. Format Output
        $formattedGroups = $clusters->map(function ($cluster, $index) {
            return [
                'id' => $index,
                'reason' => $cluster['reason'],
                'items' => $cluster['items']->map(function($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'type' => $location->type,
                        'type_label' => __("hiko.{$location->type}"),
                        'created_at' => $location->created_at,
                        'manifestations_count' => $location->total_manifestations_count,
                    ];
                })->values()->toArray()
            ];
        })->values();

        return $formattedGroups;
    }

    private function normalizeName(string $name): string
    {
        if (empty($name)) return '';
        if (function_exists('removeAccents')) {
            return trim(strtolower(str_replace(',', '', removeAccents($name))));
        }
        return trim(strtolower($name));
    }

    /**
     * Execute the merge.
     */
    public function merge(array $data): void
    {
        $payload = $data;
        $result = [];

        try {
            DB::transaction(function () use ($data, &$result) {
                $targetId = $data['target_id'];
                $sourceIds = $data['source_ids'];
                $target = Location::findOrFail($targetId);

                // Update Target Attributes based on selection
                $target->update([
                    'name' => $data['attributes']['name'],
                    'type' => $data['attributes']['type'],
                ]);

                foreach ($sourceIds as $sourceId) {
                    if ((int)$sourceId === (int)$targetId) continue;

                    // Re-link Manifestations
                    Manifestation::where('repository_id', $sourceId)->update(['repository_id' => $targetId]);
                    Manifestation::where('archive_id', $sourceId)->update(['archive_id' => $targetId]);
                    Manifestation::where('collection_id', $sourceId)->update(['collection_id' => $targetId]);

                    // Delete Source
                    Location::where('id', $sourceId)->delete();
                }

                $result = [
                    'target_id' => (int)$targetId,
                    'source_ids' => array_map('intval', $sourceIds),
                    'merged_count' => count($sourceIds),
                ];
            });

            $this->logAudit('success', $payload, $result);
            Log::info('[LocalLocationMerge] success', $result);
        } catch (\Throwable $e) {
            $this->logAudit('error', $payload, [], $e->getMessage());
            Log::error('[LocalLocationMerge] error: ' . $e->getMessage(), ['payload' => $payload]);
            throw $e;
        }
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
                'entity' => 'location',
                'operation' => 'local_merge',
                'status' => $status,
                'payload' => $payload,
                'result' => $result,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            Log::error('[LocalLocationMerge] failed to persist audit log: ' . $e->getMessage());
        }
    }
}
