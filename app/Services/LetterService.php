<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\Location;
use App\Models\OcrSnapshot;
use Illuminate\Support\Facades\DB;

class LetterService
{
    /**
     * Copy manifestations from one letter to another.
     */
    public function duplicateManifestations(Letter $sourceLetter, Letter $duplicatedLetter): void
    {
        $manifestations = $sourceLetter->manifestations()
            ->get([
                'repository_id',
                'global_repository_id',
                'archive_id',
                'global_archive_id',
                'collection_id',
                'global_collection_id',
                'signature',
                'type',
                'preservation',
                'copy',
                'l_number',
                'manifestation_notes',
                'location_note',
            ])
            ->map(fn ($manifestation) => $manifestation->toArray())
            ->all();

        if ($manifestations === []) {
            return;
        }

        $duplicatedLetter->manifestations()->createMany($manifestations);
    }

    /**
     * Copy OCR snapshots from one letter to another.
     */
    public function duplicateOcrSnapshots(Letter $sourceLetter, Letter $duplicatedLetter): void
    {
        $tenantPrefix = tenancy()->initialized ? tenancy()->tenant->table_prefix : null;

        $snapshots = OcrSnapshot::query()
            ->where('letter_id', $sourceLetter->id)
            ->when($tenantPrefix, fn ($query) => $query->where('tenant_prefix', $tenantPrefix))
            ->get();

        foreach ($snapshots as $snapshot) {
            OcrSnapshot::create([
                'tenant_id' => $snapshot->tenant_id,
                'tenant_prefix' => $snapshot->tenant_prefix,
                'letter_id' => $duplicatedLetter->id,
                'user_id' => $snapshot->user_id,
                'user_email' => $snapshot->user_email,
                'provider' => $snapshot->provider,
                'model' => $snapshot->model,
                'status' => $snapshot->status,
                'source_files' => $snapshot->source_files,
                'recognized_text' => $snapshot->recognized_text,
                'metadata' => $snapshot->metadata,
                'mapped_fields' => $snapshot->mapped_fields,
                'request_prompt' => $snapshot->request_prompt,
                'raw_response' => $snapshot->raw_response,
                'error_message' => $snapshot->error_message,
                'applied_at' => null,
                'applied_by_user_id' => null,
                'apply_mode' => null,
                'applied_field_keys' => null,
            ]);
        }
    }

    /**
     * Syncs the 'copies' array (from request) to the 'manifestations' table.
     * Handles resolving Location names to IDs (Local only for now).
     *
     * @param Letter $letter
     * @param array|null $copiesData Array of copies from the request
     * @return void
     */
    public function syncManifestations(Letter $letter, ?array $copiesData): void
    {
        $copiesData ??= [];

        DB::transaction(function () use ($letter, $copiesData) {
            $letter->manifestations()->delete();

            foreach ($copiesData as $copy) {
                if (empty(array_filter($copy))) {
                    continue;
                }

                // Resolve IDs (Local or Global)
                $repositoryIds = $this->resolveLocation($copy['repository'] ?? null, 'repository');
                $archiveIds    = $this->resolveLocation($copy['archive'] ?? null, 'archive');
                $collectionIds = $this->resolveLocation($copy['collection'] ?? null, 'collection');

                $letter->manifestations()->create([
                    'repository_id' => $repositoryIds['local_id'],
                    'global_repository_id' => $repositoryIds['global_id'],
                    'archive_id' => $archiveIds['local_id'],
                    'global_archive_id' => $archiveIds['global_id'],
                    'collection_id' => $collectionIds['local_id'],
                    'global_collection_id' => $collectionIds['global_id'],
                    'signature' => $copy['signature'] ?? null,
                    'type' => $copy['type'] ?? null,
                    'preservation' => $copy['preservation'] ?? null,
                    'copy' => $copy['copy'] ?? null,
                    'l_number' => $copy['l_number'] ?? null,
                    'manifestation_notes' => $copy['manifestation_notes'] ?? null,
                    'location_note' => $copy['location_note'] ?? null,
                ]);
            }
        });
    }

    /**
     * Parses the input string and returns local/global IDs.
     *
     * Input formats:
     * 1. "local-123" -> returns ['local_id' => 123, 'global_id' => null]
     * 2. "global-456" -> returns ['local_id' => null, 'global_id' => 456]
     * 3. "New Name" -> Creates local location -> returns ['local_id' => new_id, 'global_id' => null]
     */
    protected function resolveLocation(?string $input, string $type): array
    {
        if (is_array($input)) {
            $input = $input['value'] ?? ($input['label'] ?? null);
        }

        if (empty($input)) {
            return ['local_id' => null, 'global_id' => null];
        }

        // Check if input is a structured ID
        if (preg_match('/^(local|global)-(\d+)$/', $input, $matches)) {
            $scope = $matches[1];
            $id = (int)$matches[2];

            if ($scope === 'global') {
                return ['local_id' => null, 'global_id' => $id];
            } else {
                return ['local_id' => $id, 'global_id' => null];
            }
        }

        // Fallback for new strings or legacy data: Find or Create Local
        $name = trim($input);

        // Always create local if user typed a string.
        $location = Location::firstOrCreate(
            ['name' => $name, 'type' => $type],
            ['name' => $name, 'type' => $type]
        );

        return ['local_id' => $location->id, 'global_id' => null];
    }
}
