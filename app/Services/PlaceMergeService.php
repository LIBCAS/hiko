<?php

namespace App\Services;

use App\Models\Place;
use App\Models\GlobalPlace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlaceMergeService
{
    /**
     * Merge local tenant places into global places.
     *
     * Matching criteria: name, country, latitude, longitude must all match exactly.
     * If no match found, create new global place.
     * Update letter_place pivot to use global_place_id.
     * Delete local place after merging.
     *
     * @return array Statistics about the merge operation
     */
    public function mergeLocalPlacesToGlobal(): array
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
            // Get all local places
            $localPlaces = Place::all();

            foreach ($localPlaces as $localPlace) {
                try {
                    // Try to find matching global place using strict criteria
                    $globalPlace = $this->findMatchingGlobalPlace($localPlace);

                    if (!$globalPlace) {
                        // No match found - create new global place
                        $globalPlace = $this->createGlobalPlaceFromLocal($localPlace);
                        $created++;
                        Log::info("[PlaceMerge] Created new global place: {$globalPlace->id} for local place: {$localPlace->id}");
                    } else {
                        // Match found - merge attributes (local overwrites global if not null)
                        $this->mergeAttributesIntoGlobal($globalPlace, $localPlace);
                        $merged++;
                        Log::info("[PlaceMerge] Merged local place {$localPlace->id} into global place {$globalPlace->id}");
                    }

                    // Update letter_place pivot records
                    $this->updateLetterPlacePivot($localPlace->id, $globalPlace->id, $tenantPrefix);

                    // Delete the local place
                    $localPlace->delete();

                } catch (\Exception $e) {
                    Log::error("[PlaceMerge] Error processing local place {$localPlace->id}: " . $e->getMessage());
                    $errors[] = "Place ID {$localPlace->id}: " . $e->getMessage();
                    $skipped++;
                }
            }

            DB::commit();

            Log::info("[PlaceMerge] Merge completed. Created: $created, Merged: $merged, Skipped: $skipped");

            return [
                'success' => true,
                'merged' => $merged,
                'created' => $created,
                'skipped' => $skipped,
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[PlaceMerge] Transaction failed: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Find a global place that matches the local place based on strict criteria.
     *
     * @param Place $localPlace
     * @return GlobalPlace|null
     */
    protected function findMatchingGlobalPlace(Place $localPlace): ?GlobalPlace
    {
        $query = GlobalPlace::where('name', $localPlace->name)
            ->where('country', $localPlace->country);

        // Handle nullable latitude/longitude
        if ($localPlace->latitude !== null) {
            $query->where('latitude', $localPlace->latitude);
        } else {
            $query->whereNull('latitude');
        }

        if ($localPlace->longitude !== null) {
            $query->where('longitude', $localPlace->longitude);
        } else {
            $query->whereNull('longitude');
        }

        return $query->first();
    }

    /**
     * Create a new global place from a local place.
     *
     * @param Place $localPlace
     * @return GlobalPlace
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
     * Merge attributes from local place into global place.
     * Local place attributes overwrite global ones if they are not null.
     *
     * @param GlobalPlace $globalPlace
     * @param Place $localPlace
     * @return void
     */
    protected function mergeAttributesIntoGlobal(GlobalPlace $globalPlace, Place $localPlace): void
    {
        $updated = false;

        // Overwrite global attributes with local ones if local is not null
        if ($localPlace->division !== null && $globalPlace->division !== $localPlace->division) {
            $globalPlace->division = $localPlace->division;
            $updated = true;
        }

        if ($localPlace->note !== null && $globalPlace->note !== $localPlace->note) {
            $globalPlace->note = $localPlace->note;
            $updated = true;
        }

        if ($localPlace->alternative_names !== null && $globalPlace->alternative_names !== $localPlace->alternative_names) {
            // Merge alternative names arrays if both exist
            if (is_array($globalPlace->alternative_names) && is_array($localPlace->alternative_names)) {
                $merged = array_unique(array_merge($globalPlace->alternative_names, $localPlace->alternative_names));
                $globalPlace->alternative_names = $merged;
            } else {
                $globalPlace->alternative_names = $localPlace->alternative_names;
            }
            $updated = true;
        }

        if ($localPlace->geoname_id !== null && $globalPlace->geoname_id !== $localPlace->geoname_id) {
            $globalPlace->geoname_id = $localPlace->geoname_id;
            $updated = true;
        }

        if ($updated) {
            $globalPlace->save();
            Log::info("[PlaceMerge] Updated global place {$globalPlace->id} with local attributes");
        }
    }

    /**
     * Update letter_place pivot records to use global_place_id instead of place_id.
     *
     * @param int $localPlaceId
     * @param int $globalPlaceId
     * @param string $tenantPrefix
     * @return void
     */
    protected function updateLetterPlacePivot(int $localPlaceId, int $globalPlaceId, string $tenantPrefix): void
    {
        $pivotTable = "{$tenantPrefix}__letter_place";

        // Get all pivot records for this local place
        $pivotRecords = DB::table($pivotTable)
            ->where('place_id', $localPlaceId)
            ->get();

        foreach ($pivotRecords as $record) {
            // Check if this letter already has a link to the global place with same role
            $existingLink = DB::table($pivotTable)
                ->where('letter_id', $record->letter_id)
                ->where('global_place_id', $globalPlaceId)
                ->where('role', $record->role)
                ->first();

            if ($existingLink) {
                // Duplicate exists - just delete the local link
                DB::table($pivotTable)
                    ->where('id', $record->id)
                    ->delete();

                Log::info("[PlaceMerge] Removed duplicate link: letter {$record->letter_id}, place {$localPlaceId}, role {$record->role}");
            } else {
                // No duplicate - update to use global_place_id
                DB::table($pivotTable)
                    ->where('id', $record->id)
                    ->update([
                        'global_place_id' => $globalPlaceId,
                        'place_id' => null,
                    ]);

                Log::info("[PlaceMerge] Updated pivot: letter {$record->letter_id} now uses global_place_id {$globalPlaceId}");
            }
        }
    }
}
