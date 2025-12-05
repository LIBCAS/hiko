<?php

namespace App\Services;

use App\Models\Place;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocalPlaceMergeService
{
    /**
     * Find duplicate candidates based on selected criteria (OR logic).
     */
    public function findCandidates(array $criteria, array $options = []): Collection
    {
        if (!tenancy()->initialized) return collect();

        // Fetch all places.
        // Note: For very large datasets, this might need chunking,
        // but for tenant-scoped places, getting all is usually fine for clustering.
        $places = Place::query()
            ->select(['id', 'name', 'country', 'division', 'latitude', 'longitude', 'geoname_id', 'created_at', 'alternative_names', 'note', 'additional_name'])
            ->orderBy('created_at', 'asc')
            ->get();

        // We use an adjacency list to build clusters: [id => [connected_id, connected_id]]
        $connections = [];

        // Helper to link two IDs
        $link = function($id1, $id2) use (&$connections) {
            $connections[$id1][] = $id2;
            $connections[$id2][] = $id1;
        };

        // --- Geoname ID ---
        if (in_array('geoname_id', $criteria)) {
            $places->whereNotNull('geoname_id')
                ->groupBy('geoname_id')
                ->filter(fn($g) => $g->count() > 1)
                ->each(function($group) use ($link) {
                    $first = $group->first();
                    foreach ($group as $p) {
                        if ($p->id !== $first->id) $link($first->id, $p->id);
                    }
                });
        }

        // --- Coordinates ---
        if (in_array('coordinates', $criteria)) {
            $latTol = $options['latitude_tolerance'] ?? 0.1;
            $lonTol = $options['longitude_tolerance'] ?? 0.1;

            // Optimization: Round to 1 decimal place to create "buckets", then compare within buckets
            // This avoids O(N^2) over the whole set
            $buckets = $places->whereNotNull('latitude')->whereNotNull('longitude')->groupBy(function($p) {
                return floor($p->latitude) . '|' . floor($p->longitude);
            });

            foreach ($buckets as $bucket) {
                if ($bucket->count() < 2) continue;
                $bucketVals = $bucket->values();
                for ($i = 0; $i < count($bucketVals); $i++) {
                    for ($j = $i + 1; $j < count($bucketVals); $j++) {
                        $p1 = $bucketVals[$i];
                        $p2 = $bucketVals[$j];

                        if (abs($p1->latitude - $p2->latitude) <= $latTol &&
                            abs($p1->longitude - $p2->longitude) <= $lonTol) {
                            $link($p1->id, $p2->id);
                        }
                    }
                }
            }
        }

        // --- Name Based Checks ---
        // To optimize, we group by Country first.
        // We only compare places within the same country (or if country is null).
        $countryGroups = $places->groupBy(fn($p) => mb_strtolower(trim($p->country ?? '')));

        foreach ($countryGroups as $country => $cPlaces) {
            if ($cPlaces->count() < 2) continue;

            $items = $cPlaces->values();
            $count = $items->count();

            // Setup Helpers
            $checkNameSim = in_array('name_similarity', $criteria);
            $checkCountryName = in_array('country_and_name', $criteria);
            $checkAltNames = in_array('alternative_names', $criteria);

            $nameThreshold = $options['name_similarity_threshold'] ?? 80;
            $cnThreshold = $options['country_and_name_threshold'] ?? 80;

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $p1 = $items[$i];
                    $p2 = $items[$j];

                    $matched = false;

                    // Criterion: Alternative Names
                    if (!$matched && $checkAltNames) {
                        $names1 = array_merge([$p1->name], $p1->alternative_names ?? []);
                        $names2 = array_merge([$p2->name], $p2->alternative_names ?? []);
                        foreach ($names1 as $n1) {
                            foreach ($names2 as $n2) {
                                if (mb_strtolower(trim($n1)) === mb_strtolower(trim($n2))) {
                                    $matched = true; break 2;
                                }
                            }
                        }
                    }

                    // Criterion: Name Similarity (Levenshtein %)
                    if (!$matched && $checkNameSim) {
                        if ($this->calculateSimilarity($p1->name, $p2->name) >= $nameThreshold) {
                            $matched = true;
                        }
                    }

                    // Criterion: Country & Name (Country already matched via group)
                    if (!$matched && $checkCountryName) {
                        if ($this->calculateSimilarity($p1->name, $p2->name) >= $cnThreshold) {
                            $matched = true;
                        }
                    }

                    if ($matched) {
                        $link($p1->id, $p2->id);
                    }
                }
            }
        }

        // Build Clusters from Connections (Graph Traversal)
        $clusters = collect();
        $visited = [];

        foreach ($places as $place) {
            if (isset($visited[$place->id])) continue;
            if (!isset($connections[$place->id])) continue;

            $clusterIds = [$place->id];
            $queue = [$place->id];
            $visited[$place->id] = true;

            while (!empty($queue)) {
                $current = array_pop($queue);
                if (isset($connections[$current])) {
                    foreach ($connections[$current] as $neighbor) {
                        if (!isset($visited[$neighbor])) {
                            $visited[$neighbor] = true;
                            $clusterIds[] = $neighbor;
                            $queue[] = $neighbor;
                        }
                    }
                }
            }

            if (count($clusterIds) > 1) {
                // Get full models, sort by created_at (oldest first)
                $clusterPlaces = $places->whereIn('id', $clusterIds)->sortBy('created_at')->values();
                $clusters->push([
                    'reason' => 'Multiple Criteria', // Simplified reason as clusters merge multiple reasons
                    'places' => $clusterPlaces
                ]);
            }
        }

        return $clusters;
    }

    protected function calculateSimilarity($str1, $str2)
    {
        $len1 = strlen($str1);
        $len2 = strlen($str2);
        if ($len1 === 0 && $len2 === 0) return 100;
        if ($len1 === 0 || $len2 === 0) return 0;

        $lev = levenshtein($str1, $str2);
        $maxLen = max($len1, $len2);

        return (1 - ($lev / $maxLen)) * 100;
    }

    /**
     * Execute the merge inside a transaction.
     */
    public function merge(array $data): void
    {
        $tenantPrefix = tenancy()->tenant->table_prefix;

        DB::transaction(function () use ($data, $tenantPrefix) {
            $targetPlace = Place::findOrFail($data['target_id']);

            // Update Target Attributes
            $targetPlace->update([
                'name' => $data['attributes']['name'],
                'country' => $data['attributes']['country'],
                'division' => $data['attributes']['division'] ?? null,
                'latitude' => $data['attributes']['latitude'] ?? null,
                'longitude' => $data['attributes']['longitude'] ?? null,
                'geoname_id' => $data['attributes']['geoname_id'] ?? null,
                'note' => $data['attributes']['note'] ?? null,
                'additional_name' => $data['attributes']['additional_name'] ?? null,
                // User selects ONE set of alternative names
                'alternative_names' => $data['attributes']['alternative_names'] ?? [],
            ]);

            // Re-link Pivot Tables (Letters)
            $pivotTable = "{$tenantPrefix}__letter_place";

            foreach ($data['source_ids'] as $sourceId) {
                // Move relationships
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

                // Delete Source
                Place::where('id', $sourceId)->delete();
            }
        });
    }
}
