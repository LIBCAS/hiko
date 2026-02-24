<?php

namespace App\Services;

use App\Models\Location;
use App\Models\GlobalLocation;
use App\Models\MergeAuditLog;
use App\Models\Manifestation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class GlobalLocationMergeService
{
    /**
     * Preview merges based on criteria.
     */
    public function previewMerges(array $criteria, array $options = [], array $filters = []): Collection
    {
        if (!tenancy()->initialized) {
            throw new \Exception('Tenancy not initialized');
        }

        $localLocations = Location::query();

        // Apply filters
        if (!empty($filters['name'])) {
            $localLocations->where('name', 'like', "%{$filters['name']}%");
        }
        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            $localLocations->where('type', $filters['type']);
        }

        $localLocations = $localLocations->get();
        $previewData = collect();

        foreach ($localLocations as $local) {
            $strategy = $this->determineMergeStrategy($local, $criteria, $options);

            // Strategy Filter
            if (!empty($filters['strategy']) && $filters['strategy'] !== 'all') {
                if ($filters['strategy'] !== $strategy['type']) continue;
            }

            $previewData->push([
                'local' => $local,
                'strategy' => $strategy['type'],
                'global' => $strategy['global_location'],
                'reason' => $strategy['reason'],
            ]);
        }

        return $previewData;
    }

    /**
     * Determine if local matches a global location.
     */
    public function determineMergeStrategy(Location $local, array $criteria, array $options = []): array
    {
        $globalLocations = GlobalLocation::all();

        // 1. Strict Type Match (Default: enabled)
        $strictType = in_array('type', $criteria);

        // 2. Name Similarity (Optional)
        $useSimilarity = in_array('name_similarity', $criteria);
        $threshold = $options['name_similarity_threshold'] ?? 100; // Default to exact match if not set

        foreach ($globalLocations as $global) {
            // Check Type
            if ($strictType && $local->type !== $global->type) {
                continue;
            }

            $name1 = mb_strtolower(trim($local->name));
            $name2 = mb_strtolower(trim($global->name));

            // Logic:
            // If "Name Similarity" is checked, use percentage check.
            // If NOT checked, strictly use Exact Match (==).

            if ($useSimilarity) {
                $pct = 0.0;
                similar_text($name1, $name2, $pct);

                if ($pct >= $threshold) {
                     return [
                        'type' => 'merge',
                        'global_location' => $global,
                        'reason' => __('hiko.similarity_percentage', ['percent' => round($pct, 2)]),
                    ];
                }
            } else {
                // Exact match fallback
                if ($name1 === $name2) {
                    return [
                        'type' => 'merge',
                        'global_location' => $global,
                        'reason' => __('hiko.exact_match')
                    ];
                }
            }
        }

        return [
            'type' => 'move',
            'global_location' => null,
            'reason' => 'no_match',
        ];
    }

    /**
     * Execute the merge.
     */
    public function executeMerge(array $selectedIds, array $criteria, array $options = [], array $mergeAttrs = []): array
    {
        $payload = [
            'selected_ids' => $selectedIds,
            'criteria' => $criteria,
            'options' => $options,
        ];

        $merged = 0;
        $created = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            $localLocations = Location::whereIn('id', $selectedIds)->get();

            foreach ($localLocations as $local) {
                try {
                    $strategy = $this->determineMergeStrategy($local, $criteria, $options);
                    $globalLocation = null;

                    if ($strategy['type'] === 'merge') {
                        $globalLocation = $strategy['global_location'];
                        $merged++;
                    } else {
                        // Create new Global Location
                        $globalLocation = GlobalLocation::create([
                            'name' => $local->name,
                            'type' => $local->type,
                        ]);
                        $created++;
                    }

                    // Re-link Manifestations
                    $this->relinkManifestations($local, $globalLocation);

                    // Delete Local
                    $local->delete();

                } catch (\Exception $e) {
                    $errors[] = "Location {$local->id}: " . $e->getMessage();
                    $skipped++;
                }
            }
            DB::commit();

            $result = [
                'success' => true,
                'merged' => $merged,
                'created' => $created,
                'skipped' => $skipped,
                'errors' => $errors
            ];
            $this->logAudit('success', $payload, $result);
            Log::info('[GlobalLocationMerge] success', $result);
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            $result = ['success' => false, 'error' => $e->getMessage()];
            $this->logAudit('error', $payload, $result, $e->getMessage());
            Log::error('[GlobalLocationMerge] error: ' . $e->getMessage(), ['payload' => $payload]);
            return $result;
        }
    }

    /**
     * Update all manifestations pointing to local ID to use global ID.
     */
    protected function relinkManifestations(Location $local, GlobalLocation $global): void
    {
        // We must update the specific column matching the location type
        // e.g. if local location is type='repository', update `global_repository_id`
        // However, a location might be used in mismatching columns (legacy data), so safe to update all 3 roles if IDs match.
        // BUT, Manifestation table has specific columns: repository_id, archive_id, collection_id.

        // Strategy: Find where this local ID is used and update the corresponding Global column.

        // 1. As Repository
        Manifestation::where('repository_id', $local->id)
            ->update([
                'repository_id' => null,
                'global_repository_id' => $global->id
            ]);

        // 2. As Archive
        Manifestation::where('archive_id', $local->id)
            ->update([
                'archive_id' => null,
                'global_archive_id' => $global->id
            ]);

        // 3. As Collection
        Manifestation::where('collection_id', $local->id)
            ->update([
                'collection_id' => null,
                'global_collection_id' => $global->id
            ]);
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
                'operation' => 'global_merge',
                'status' => $status,
                'payload' => $payload,
                'result' => $result,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            Log::error('[GlobalLocationMerge] failed to persist audit log: ' . $e->getMessage());
        }
    }
}
