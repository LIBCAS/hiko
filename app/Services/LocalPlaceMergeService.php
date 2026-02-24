<?php

namespace App\Services;

use App\Models\MergeAuditLog;
use App\Models\Place;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocalPlaceMergeService
{
    public function findCandidates(array $criteria, array $options = []): Collection
    {
        if (!tenancy()->initialized) return collect();

        $places = Place::query()
            ->select(['id', 'name', 'country', 'division', 'latitude', 'longitude', 'geoname_id', 'created_at', 'alternative_names', 'note', 'additional_name'])
            ->orderBy('created_at', 'asc')
            ->get();

        $places->each(function ($place) {
            $place->normalized_name = $this->normalizeName($place->name);
        });

        $connections = [];
        $link = function ($id1, $id2) use (&$connections) {
            $connections[$id1][] = $id2;
            $connections[$id2][] = $id1;
        };

        // --- Geoname ID ---
        if (in_array('geoname_id', $criteria)) {
            $places->whereNotNull('geoname_id')
                ->groupBy('geoname_id')
                ->filter(fn($g) => $g->count() > 1)
                ->each(function ($group) use ($link) {
                    $first = $group->first();
                    foreach ($group as $p) if ($p->id !== $first->id) $link($first->id, $p->id);
                });
        }

        // --- Coordinates ---
        if (in_array('coordinates', $criteria)) {
            $latTol = $options['latitude_tolerance'] ?? 0.1;
            $lonTol = $options['longitude_tolerance'] ?? 0.1;
            $validCoords = $places->whereNotNull('latitude')->whereNotNull('longitude')->values();
            $count = $validCoords->count();

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $p1 = $validCoords[$i];
                    $p2 = $validCoords[$j];
                    if (
                        abs($p1->latitude - $p2->latitude) <= $latTol &&
                        abs($p1->longitude - $p2->longitude) <= $lonTol
                    ) {
                        $link($p1->id, $p2->id);
                    }
                }
            }
        }

        // --- Name Similarity (Independent of Country) ---
        if (in_array('name_similarity', $criteria)) {
            $threshold = $options['name_similarity_threshold'] ?? 80;
            $items = $places->values()->all();
            $count = count($items);

            for ($i = 0; $i < $count; $i++) {
                if (strlen($items[$i]->normalized_name) < 3) continue;
                for ($j = $i + 1; $j < $count; $j++) {
                    // Pre-check first char for speed
                    if ($items[$i]->normalized_name[0] !== $items[$j]->normalized_name[0]) continue;

                    $pct = 0.0;
                    similar_text($items[$i]->normalized_name, $items[$j]->normalized_name, $pct);
                    if ($pct >= $threshold) {
                        $link($items[$i]->id, $items[$j]->id);
                    }
                }
            }
        }

        // --- Country AND Name (Combined) ---
        if (in_array('country_and_name', $criteria)) {
            $threshold = $options['country_and_name_threshold'] ?? 80;
            // Group by country first
            $countryGroups = $places->groupBy(fn($p) => mb_strtolower(trim($p->country ?? '')));

            foreach ($countryGroups as $groupItems) {
                if ($groupItems->count() < 2) continue;
                $items = $groupItems->values()->all();
                $count = count($items);

                for ($i = 0; $i < $count; $i++) {
                    if (strlen($items[$i]->normalized_name) < 3) continue;
                    for ($j = $i + 1; $j < $count; $j++) {
                        // Country matches by group definition, check name
                        $pct = 0.0;
                        similar_text($items[$i]->normalized_name, $items[$j]->normalized_name, $pct);
                        if ($pct >= $threshold) {
                            $link($items[$i]->id, $items[$j]->id);
                        }
                    }
                }
            }
        }

        // --- Alternative Names ---
        if (in_array('alternative_names', $criteria)) {
            // Simplified O(N^2) comparison for robustness over optimization here
            $count = $places->count();
            for ($i = 0; $i < $count; $i++) {
                $p1 = $places[$i];
                $names1 = array_merge([$p1->name], $p1->alternative_names ?? []);

                for ($j = $i + 1; $j < $count; $j++) {
                    $p2 = $places[$j];
                    $names2 = array_merge([$p2->name], $p2->alternative_names ?? []);

                    foreach ($names1 as $n1) {
                        foreach ($names2 as $n2) {
                            if (mb_strtolower(trim($n1)) === mb_strtolower(trim($n2))) {
                                $link($p1->id, $p2->id);
                                break 2; // Match found, break inner loops
                            }
                        }
                    }
                }
            }
        }

        // Build Clusters
        $clusters = collect();
        $visited = [];
        foreach ($places as $place) {
            if (isset($visited[$place->id]) || !isset($connections[$place->id])) continue;
            $clusterIds = [$place->id];
            $queue = [$place->id];
            $visited[$place->id] = true;

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
                    'items' => $places->whereIn('id', $clusterIds)->sortBy('created_at')->values()
                ]);
            }
        }

        return $clusters;
    }

    private function normalizeName(string $name): string
    {
        return trim(strtolower(str_replace(',', '', removeAccents($name))));
    }

    public function merge(array $data): void
    {
        $payload = $data;
        $tenantPrefix = tenancy()->tenant->table_prefix;

        try {
            DB::transaction(function () use ($data, $tenantPrefix) {
                $targetPlace = Place::findOrFail($data['target_id']);

                $targetPlace->update([
                    'name' => $data['attributes']['name'],
                    'country' => $data['attributes']['country'],
                    'division' => $data['attributes']['division'] ?? null,
                    'latitude' => $data['attributes']['latitude'] ?? null,
                    'longitude' => $data['attributes']['longitude'] ?? null,
                    'geoname_id' => $data['attributes']['geoname_id'] ?? null,
                    'note' => $data['attributes']['note'] ?? null,
                    'additional_name' => $data['attributes']['additional_name'] ?? null,
                    'alternative_names' => $data['attributes']['alternative_names'] ?? [],
                ]);

                $pivotTable = "{$tenantPrefix}__letter_place";

                foreach ($data['source_ids'] as $sourceId) {
                    if ((int)$sourceId === (int)$data['target_id']) {
                        continue;
                    }

                    $links = DB::table($pivotTable)->where('place_id', $sourceId)->get();
                    foreach ($links as $link) {
                        $exists = DB::table($pivotTable)
                            ->where('letter_id', $link->letter_id)
                            ->where('place_id', $targetPlace->id)
                            ->where('role', $link->role)
                            ->exists();

                        if ($exists) {
                            DB::table($pivotTable)->where('id', $link->id)->delete();
                        } else {
                            DB::table($pivotTable)->where('id', $link->id)->update(['place_id' => $targetPlace->id]);
                        }
                    }

                    Place::where('id', $sourceId)->delete();
                }
            });

            $result = [
                'target_id' => (int)$data['target_id'],
                'source_ids' => array_map('intval', $data['source_ids']),
                'merged_count' => count($data['source_ids']),
            ];
            $this->logAudit('success', $payload, $result);
            Log::info('[LocalPlaceMerge] success', $result);
        } catch (\Throwable $e) {
            $this->logAudit('error', $payload, [], $e->getMessage());
            Log::error('[LocalPlaceMerge] error: ' . $e->getMessage(), ['payload' => $payload]);
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
                'entity' => 'place',
                'operation' => 'local_merge',
                'status' => $status,
                'payload' => $payload,
                'result' => $result,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            Log::error('[LocalPlaceMerge] failed to persist audit log: ' . $e->getMessage());
        }
    }
}
