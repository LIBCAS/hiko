<?php

namespace App\Services;

use App\Models\Place;
use App\Models\GlobalPlace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class GlobalPlaceMergeService
{
    /**
     * Generate preview data for merging based on selected criteria.
     *
     * @param array $criteria Selected merge criteria
     * @param array $options Threshold values
     * @param array $filters Filters for preview display
     * @return Collection
     */
    public function previewMerges(array $criteria, array $options = [], array $filters = []): Collection
    {
        if (!tenancy()->initialized) {
            throw new \Exception('Tenancy not initialized');
        }

        $localPlaces = Place::query();

        // Apply filters
        if (!empty($filters['name'])) {
            $localPlaces->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['name']}%")
                  ->orWhere('country', 'like', "%{$filters['name']}%")
                  ->orWhere('division', 'like', "%{$filters['name']}%");
            });
        }

        if (!empty($filters['country'])) {
            $localPlaces->where('country', 'like', "%{$filters['country']}%");
        }

        $localPlaces = $localPlaces->get();

        $previewData = collect();

        foreach ($localPlaces as $localPlace) {
            $strategy = $this->determineMergeStrategy($localPlace, $criteria, $options);

            // Apply strategy filter
            if (!empty($filters['strategy']) && $filters['strategy'] !== 'all') {
                if ($filters['strategy'] !== $strategy['type']) {
                    continue;
                }
            }

            // Apply reason filter
            if (!empty($filters['reason']) && $filters['reason'] !== 'all') {
                if ($strategy['reason'] !== $filters['reason']) {
                    continue;
                }
            }

            $previewData->push([
                'local' => $localPlace,
                'strategy' => $strategy['type'],
                'global' => $strategy['global_place'],
                'reason' => $strategy['reason'],
            ]);
        }

        return $previewData;
    }

    /**
     * Determine the merge strategy for a local place.
     *
     * @param Place $local
     * @param array $criteria
     * @param array $options
     * @return array ['type' => 'merge|move', 'global_place' => GlobalPlace|null, 'reason' => string|null]
     */
    public function determineMergeStrategy(Place $local, array $criteria, array $options = []): array
    {
        $globalPlaces = GlobalPlace::all();

        // Try each criterion in order of strength (strongest first)
        $criteriaOrder = config('global_place_merge.criteria_order', [
            'geoname_id',
            'alternative_names',
            'country_and_name',
            'name_similarity',
            'coordinates'
        ]);

        foreach ($criteriaOrder as $criterion) {
            if (!in_array($criterion, $criteria)) {
                continue;
            }

            foreach ($globalPlaces as $global) {
                $matches = match ($criterion) {
                    'geoname_id' => $this->checkGeonameIdMatch($local, $global),
                    'alternative_names' => $this->checkAlternativeNamesMatch($local, $global),
                    'country_and_name' => $this->checkCountryAndNameMatch($local, $global, $options['country_and_name_threshold'] ?? config('global_place_merge.country_and_name_threshold')),
                    'name_similarity' => $this->checkNameSimilarity($local, $global, $options['name_similarity_threshold'] ?? config('global_place_merge.name_similarity_threshold')),
                    'coordinates' => $this->checkCoordinatesMatch($local, $global, $options['latitude_tolerance'] ?? config('global_place_merge.latitude_tolerance'), $options['longitude_tolerance'] ?? config('global_place_merge.longitude_tolerance')),
                    default => false,
                };

                if ($matches) {
                    // Debug logging to understand why certain criteria match
                    Log::debug("[GlobalPlaceMerge] Match found", [
                        'local_place' => $local->name,
                        'local_geoname_id' => $local->geoname_id,
                        'global_place' => $global->name,
                        'global_geoname_id' => $global->geoname_id,
                        'criterion' => $criterion,
                        'criteria_checked' => $criteria,
                    ]);

                    return [
                        'type' => 'merge',
                        'global_place' => $global,
                        'reason' => $criterion,
                    ];
                }
            }
        }

        // No match found - will be moved as new global place
        return [
            'type' => 'move',
            'global_place' => null,
            'reason' => null,
        ];
    }

    /**
     * Check if two places match by geoname_id.
     */
    public function checkGeonameIdMatch(Place $local, GlobalPlace $global): bool
    {
        if ($local->geoname_id === null || $global->geoname_id === null) {
            return false;
        }

        // Use == instead of === to handle type coercion (string vs int)
        // Cast both to integers to ensure proper comparison
        return (int)$local->geoname_id === (int)$global->geoname_id;
    }

    /**
     * Check if two places match by alternative names.
     */
    public function checkAlternativeNamesMatch(Place $local, GlobalPlace $global): bool
    {
        $localNames = array_merge(
            [$local->name],
            is_array($local->alternative_names) ? $local->alternative_names : []
        );

        $globalNames = array_merge(
            [$global->name],
            is_array($global->alternative_names) ? $global->alternative_names : []
        );

        foreach ($localNames as $localName) {
            foreach ($globalNames as $globalName) {
                if (strtolower(trim($localName)) === strtolower(trim($globalName))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if two places match by name similarity threshold.
     */
    public function checkNameSimilarity(Place $local, GlobalPlace $global, int $threshold): bool
    {
        if (!function_exists('calculateSimilarityPercentage')) {
            return false;
        }

        $similarity = calculateSimilarityPercentage($local->name, $global->name);
        return $similarity >= $threshold;
    }

    /**
     * Check if two places match by coordinates (both latitude AND longitude).
     * Both conditions must be true for a match.
     */
    public function checkCoordinatesMatch(Place $local, GlobalPlace $global, float $latitudeTolerance, float $longitudeTolerance): bool
    {
        // First check: both must have coordinates
        if ($local->latitude === null || $global->latitude === null ||
            $local->longitude === null || $global->longitude === null) {
            return false;
        }

        // Second check: latitude must be within tolerance
        $latitudeMatch = abs((float)$local->latitude - (float)$global->latitude) <= $latitudeTolerance;
        if (!$latitudeMatch) {
            return false;
        }

        // Third check: longitude must be within tolerance
        $longitudeMatch = abs((float)$local->longitude - (float)$global->longitude) <= $longitudeTolerance;
        return $longitudeMatch;
    }

    /**
     * Check if two places match by country AND name similarity (combined criterion).
     * Both conditions must be true for a match.
     */
    public function checkCountryAndNameMatch(Place $local, GlobalPlace $global, int $threshold): bool
    {
        // First check: countries must match
        if ($local->country === null || $global->country === null) {
            return false;
        }

        $countriesMatch = strtolower(trim($local->country)) === strtolower(trim($global->country));
        if (!$countriesMatch) {
            return false;
        }

        // Second check: names must be similar above threshold
        if (!function_exists('calculateSimilarityPercentage')) {
            return false;
        }

        $similarity = calculateSimilarityPercentage($local->name, $global->name);
        return $similarity >= $threshold;
    }

    /**
     * Execute the merge for selected places based on criteria.
     *
     * @param array $selectedPlaceIds
     * @param array $criteria
     * @param array $options
     * @param array $mergeAttrs User-selected attribute preferences for each place
     * @return array
     */
    public function executeMerge(array $selectedPlaceIds, array $criteria, array $options = [], array $mergeAttrs = []): array
    {
        if (!tenancy()->initialized) {
            throw new \Exception('Tenancy not initialized');
        }

        $tenant = tenancy()->tenant;
        $tenantPrefix = $tenant->table_prefix;

        $merged = 0;
        $created = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            $localPlaces = Place::whereIn('id', $selectedPlaceIds)->get();

            foreach ($localPlaces as $localPlace) {
                try {
                    $strategy = $this->determineMergeStrategy($localPlace, $criteria, $options);

                    // Get user-selected attributes for this place (if any)
                    $userAttrs = $mergeAttrs[$localPlace->id] ?? [];

                    if ($strategy['type'] === 'merge' && $strategy['global_place']) {
                        // Merge into existing global place
                        $globalPlace = $strategy['global_place'];
                        $this->mergeAttributesIntoGlobal($globalPlace, $localPlace, $userAttrs);
                        $merged++;
                        Log::info("[GlobalPlaceMerge] Merged local place {$localPlace->id} into global place {$globalPlace->id} (reason: {$strategy['reason']})");
                    } else {
                        // Move as new global place
                        $globalPlace = $this->createGlobalPlaceFromLocal($localPlace);
                        $created++;
                        Log::info("[GlobalPlaceMerge] Created new global place: {$globalPlace->id} for local place: {$localPlace->id}");
                    }

                    // Update letter_place pivot records
                    $this->updateLetterPlacePivot($localPlace->id, $globalPlace->id, $tenantPrefix);

                    // Delete the local place
                    $localPlace->delete();

                } catch (\Exception $e) {
                    Log::error("[GlobalPlaceMerge] Error processing local place {$localPlace->id}: " . $e->getMessage());
                    $errors[] = "Place ID {$localPlace->id}: " . $e->getMessage();
                    $skipped++;
                }
            }

            DB::commit();

            Log::info("[GlobalPlaceMerge] Merge completed. Created: $created, Merged: $merged, Skipped: $skipped");

            return [
                'success' => true,
                'merged' => $merged,
                'created' => $created,
                'skipped' => $skipped,
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[GlobalPlaceMerge] Transaction failed: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a new global place from a local place.
     */
    protected function createGlobalPlaceFromLocal(Place $localPlace): GlobalPlace
    {
        return GlobalPlace::create([
            'name' => $localPlace->name,
            'country' => $localPlace->country,
            'division' => $localPlace->division,
            'note' => $localPlace->note,
            'latitude' => $localPlace->latitude,
            'longitude' => $localPlace->longitude,
            'alternative_names' => $localPlace->alternative_names,
            'geoname_id' => $localPlace->geoname_id,
        ]);
    }

    /**
     * Merge attributes from local place into global place based on user selections.
     *
     * @param GlobalPlace $globalPlace
     * @param Place $localPlace
     * @param array $userAttrs User-selected preferences: ['name' => 'local'|'global', 'country' => ...]
     */
    protected function mergeAttributesIntoGlobal(GlobalPlace $globalPlace, Place $localPlace, array $userAttrs = []): void
    {
        $updated = false;
        $tenantName = tenancy()->tenant->key;

        // Helper function to determine which value to use
        $getValue = function($attr) use ($userAttrs, $localPlace, $globalPlace) {
            $preference = $userAttrs[$attr] ?? 'global'; // Default to global
            if ($preference === 'local') {
                return $localPlace->$attr;
            } else {
                return $globalPlace->$attr;
            }
        };

        // Update attributes based on user selection
        $newName = $getValue('name');
        if ($newName !== null && $globalPlace->name !== $newName) {
            $globalPlace->name = $newName;
            $updated = true;
        }

        $newCountry = $getValue('country');
        if ($newCountry !== null && $globalPlace->country !== $newCountry) {
            $globalPlace->country = $newCountry;
            $updated = true;
        }

        $newDivision = $getValue('division');
        if ($newDivision !== null && $globalPlace->division !== $newDivision) {
            $globalPlace->division = $newDivision;
            $updated = true;
        }

        $newLatitude = $getValue('latitude');
        if ($newLatitude !== null && $globalPlace->latitude !== $newLatitude) {
            $globalPlace->latitude = $newLatitude;
            $updated = true;
        }

        $newLongitude = $getValue('longitude');
        if ($newLongitude !== null && $globalPlace->longitude !== $newLongitude) {
            $globalPlace->longitude = $newLongitude;
            $updated = true;
        }

        $newGeonameId = $getValue('geoname_id');
        if ($newGeonameId !== null && $globalPlace->geoname_id !== $newGeonameId) {
            $globalPlace->geoname_id = $newGeonameId;
            $updated = true;
        }

        // Always merge alternative names (combine both)
        if ($localPlace->alternative_names !== null) {
            if (is_array($globalPlace->alternative_names) && is_array($localPlace->alternative_names)) {
                $merged = array_unique(array_merge($globalPlace->alternative_names, $localPlace->alternative_names));
                if ($globalPlace->alternative_names !== $merged) {
                    $globalPlace->alternative_names = $merged;
                    $updated = true;
                }
            } elseif (!$globalPlace->alternative_names) {
                $globalPlace->alternative_names = $localPlace->alternative_names;
                $updated = true;
            }
        }

        // Merge additional name (concatenate)
        if ($localPlace->additional_name !== null) {
            if ($globalPlace->additional_name) {
                $combinedAdditionalName = $globalPlace->additional_name . "\n[" . $tenantName . "]: " . $localPlace->additional_name;
            } else {
                $combinedAdditionalName = "[" . $tenantName . "]: " . $localPlace->additional_name;
            }
            if ($globalPlace->additional_name !== $combinedAdditionalName) {
                $globalPlace->additional_name = $combinedAdditionalName;
                $updated = true;
            }
        }

        // Merge note (concatenate)
        if ($localPlace->note !== null) {
            if ($globalPlace->note) {
                $combinedNote = $globalPlace->note . "\n[" . $tenantName . "]: " . $localPlace->note;
            } else {
                $combinedNote = "[" . $tenantName . "]: " . $localPlace->note;
            }
            if ($globalPlace->note !== $combinedNote) {
                $globalPlace->note = $combinedNote;
                $updated = true;
            }
        }

        if ($updated) {
            $globalPlace->save();
            Log::info("[GlobalPlaceMerge] Updated global place {$globalPlace->id} with selected attributes from local place {$localPlace->id}");
        }
    }

    /**
     * Update letter_place pivot records to use global_place_id.
     */
    protected function updateLetterPlacePivot(int $localPlaceId, int $globalPlaceId, string $tenantPrefix): void
    {
        $pivotTable = "{$tenantPrefix}__letter_place";

        $pivotRecords = DB::table($pivotTable)
            ->where('place_id', $localPlaceId)
            ->get();

        foreach ($pivotRecords as $record) {
            $existingLink = DB::table($pivotTable)
                ->where('letter_id', $record->letter_id)
                ->where('global_place_id', $globalPlaceId)
                ->where('role', $record->role)
                ->first();

            if ($existingLink) {
                DB::table($pivotTable)->where('id', $record->id)->delete();
                Log::info("[GlobalPlaceMerge] Removed duplicate link: letter {$record->letter_id}, place {$localPlaceId}, role {$record->role}");
            } else {
                DB::table($pivotTable)
                    ->where('id', $record->id)
                    ->update([
                        'global_place_id' => $globalPlaceId,
                        'place_id' => null,
                    ]);
                Log::info("[GlobalPlaceMerge] Updated pivot: letter {$record->letter_id} now uses global_place_id {$globalPlaceId}");
            }
        }
    }
}
