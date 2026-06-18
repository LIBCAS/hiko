<?php

namespace App\Services;

use App\Models\InterTenantTransferRequest;
use App\Models\Letter;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class InterTenantLetterTransferService
{
    private const MAPPING_TYPES = ['identities', 'places', 'keywords', 'locations'];

    public function __construct(private InterTenantLetterTransferData $data)
    {
    }

    public function saveDraftMappings(
        InterTenantTransferRequest $request,
        Tenant $targetTenant,
        array $mappings
    ): array {
        if (!$request->isPending() || (int) $request->target_tenant_id !== (int) $targetTenant->id) {
            throw new RuntimeException(__('hiko.transfer_not_approvable'));
        }

        $sourceTenant = $request->sourceTenant()->firstOrFail();
        $payload = $this->data->load($sourceTenant, $request->source_record_ids);
        $draft = $this->validateDraftMappings($payload['dependencies'], $targetTenant, $mappings);

        DB::connection('mysql')->transaction(function () use ($request, $draft) {
            $lockedRequest = InterTenantTransferRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedRequest->isPending()) {
                throw new RuntimeException(__('hiko.transfer_not_approvable'));
            }

            $lockedRequest->update(['mappings' => $draft]);
        });

        return $draft;
    }

    public function restoreDraftMappings(
        $dependencies,
        Tenant $targetTenant,
        array $mappings
    ): array {
        $restored = $this->emptyDraftMappings($dependencies);
        $warnings = [];

        foreach (self::MAPPING_TYPES as $type) {
            $provided = is_array($mappings[$type] ?? null) ? $mappings[$type] : [];

            foreach ($dependencies[$type] as $source) {
                if (!array_key_exists($source->id, $provided)) {
                    continue;
                }

                $value = trim((string) $provided[$source->id]);
                if ($value === '') {
                    $restored[$type][(int) $source->id] = '';
                    continue;
                }

                try {
                    $this->validateMappingReference($type, $source, $targetTenant, $value);
                    $restored[$type][(int) $source->id] = $value;
                } catch (RuntimeException $e) {
                    $restored[$type][(int) $source->id] = '';
                    $warnings[] = $e->getMessage();
                }
            }
        }

        return [
            'mappings' => $restored,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    public function approve(
        InterTenantTransferRequest $request,
        Tenant $targetTenant,
        User $approver,
        array $mappings
    ): array {
        if (!$request->isPending() || (int) $request->target_tenant_id !== (int) $targetTenant->id) {
            throw new RuntimeException(__('hiko.transfer_not_approvable'));
        }

        $sourceTenant = $request->sourceTenant()->firstOrFail();
        $payload = $this->data->load($sourceTenant, $request->source_record_ids);
        $normalizedMappings = $this->validateMappings($payload['dependencies'], $targetTenant, $mappings);
        $this->validateMediaFiles($sourceTenant, $payload['media']);

        $createdMediaDirectories = [];

        try {
            $result = DB::connection('mysql')->transaction(function () use (
                $payload,
                $sourceTenant,
                $targetTenant,
                $approver,
                $normalizedMappings,
                $request,
                &$createdMediaDirectories
            ) {
                $lockedRequest = InterTenantTransferRequest::query()
                    ->whereKey($request->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$lockedRequest->isPending()) {
                    throw new RuntimeException(__('hiko.transfer_not_approvable'));
                }

                $targetPrefix = $targetTenant->table_prefix . '__';
                $letterIdMap = [];

                foreach ($payload['letters'] as $sourceLetter) {
                    $attributes = (array) $sourceLetter;
                    unset($attributes['id'], $attributes['uuid'], $attributes['created_at'], $attributes['updated_at']);
                    $attributes['uuid'] = (string) Str::uuid();
                    $attributes['history'] = null;
                    $attributes['status'] = Letter::DRAFT;
                    $attributes['approval'] = Letter::NOT_APPROVED;
                    $attributes['created_at'] = now();
                    $attributes['updated_at'] = now();

                    $letterIdMap[(int) $sourceLetter->id] = DB::connection('mysql')
                        ->table("{$targetPrefix}letters")
                        ->insertGetId($attributes);
                }

                $this->copyIdentityRows($payload['identity_rows'], $targetPrefix, $letterIdMap, $normalizedMappings);
                $this->copyPlaceRows($payload['place_rows'], $targetPrefix, $letterIdMap, $normalizedMappings);
                $this->copyKeywordRows($payload['keyword_rows'], $targetPrefix, $letterIdMap, $normalizedMappings);
                $this->copyManifestations($payload['manifestations'], $targetPrefix, $letterIdMap, $normalizedMappings);

                foreach ($letterIdMap as $targetLetterId) {
                    DB::connection('mysql')->table("{$targetPrefix}letter_user")->insert([
                        'letter_id' => $targetLetterId,
                        'user_id' => $approver->id,
                    ]);
                }

                $this->copyMedia(
                    $payload['media'],
                    $sourceTenant,
                    $targetTenant,
                    $targetPrefix,
                    $letterIdMap,
                    $createdMediaDirectories
                );

                $result = array_merge($lockedRequest->result ?? [], [
                    'letter_id_map' => $letterIdMap,
                    'letter_count' => count($letterIdMap),
                    'media_count' => $payload['media']->count(),
                ]);

                $lockedRequest->update([
                    'status' => InterTenantTransferRequest::STATUS_COMPLETED,
                    'mappings' => $normalizedMappings,
                    'result' => $result,
                    'final_snapshot' => $this->snapshot($payload, $normalizedMappings, $result),
                    'error_message' => null,
                    'decided_by_user_id' => $approver->id,
                    'decided_by_name' => $approver->name,
                    'decided_by_email' => $approver->email,
                    'decided_at' => now(),
                ]);

                return $result;
            });
        } catch (Throwable $e) {
            foreach ($createdMediaDirectories as $directory) {
                File::deleteDirectory($directory);
            }

            $request->refresh();
            if ($request->isPending()) {
                $request->update([
                    'status' => InterTenantTransferRequest::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                    'decided_by_user_id' => $approver->id,
                    'decided_by_name' => $approver->name,
                    'decided_by_email' => $approver->email,
                    'decided_at' => now(),
                ]);
            }

            throw $e;
        }

        return $result;
    }

    private function validateMappings($dependencies, Tenant $targetTenant, array $mappings): array
    {
        $normalized = [];

        foreach (self::MAPPING_TYPES as $type) {
            $sourceRecords = $dependencies[$type];
            $provided = $mappings[$type] ?? [];
            $normalized[$type] = [];

            foreach ($sourceRecords as $source) {
                $normalized[$type][(int) $source->id] = $this->validateMappingReference(
                    $type,
                    $source,
                    $targetTenant,
                    $provided[$source->id] ?? null
                );
            }
        }

        return $normalized;
    }

    private function validateDraftMappings($dependencies, Tenant $targetTenant, array $mappings): array
    {
        $draft = $this->emptyDraftMappings($dependencies);

        foreach (self::MAPPING_TYPES as $type) {
            $provided = is_array($mappings[$type] ?? null) ? $mappings[$type] : [];

            foreach ($dependencies[$type] as $source) {
                $value = trim((string) ($provided[$source->id] ?? ''));
                if ($value !== '') {
                    $this->validateMappingReference($type, $source, $targetTenant, $value);
                }

                $draft[$type][(int) $source->id] = $value;
            }
        }

        return $draft;
    }

    private function emptyDraftMappings($dependencies): array
    {
        $draft = [];

        foreach (self::MAPPING_TYPES as $type) {
            $draft[$type] = $dependencies[$type]
                ->mapWithKeys(fn ($source) => [(int) $source->id => ''])
                ->all();
        }

        return $draft;
    }

    private function validateMappingReference(
        string $type,
        object $source,
        Tenant $targetTenant,
        mixed $value
    ): array {
        $reference = $this->parseMappingReference($value);
        if (!$reference || ($type === 'identities' && $reference['scope'] !== 'local')) {
            throw new RuntimeException(__('hiko.transfer_mapping_missing', [
                'type' => __('hiko.' . $type),
                'id' => $source->id,
            ]));
        }

        $table = $reference['scope'] === 'global'
            ? 'global_' . $type
            : "{$targetTenant->table_prefix}__{$type}";
        $target = DB::connection('mysql')->table($table)->where('id', $reference['id'])->first();
        if (!$target) {
            throw new RuntimeException(__('hiko.transfer_mapping_target_missing', [
                'type' => __('hiko.' . $type),
                'id' => $reference['id'],
            ]));
        }

        if ($type === 'locations' && $source->type !== $target->type) {
            throw new RuntimeException(__('hiko.transfer_location_type_mismatch'));
        }

        if ($type === 'identities' && $source->type !== $target->type) {
            throw new RuntimeException(__('hiko.transfer_identity_type_mismatch'));
        }

        return $reference;
    }

    private function parseMappingReference(mixed $value): ?array
    {
        if (!is_string($value) || !preg_match('/^(local|global)-([1-9]\d*)$/', $value, $matches)) {
            return null;
        }

        return [
            'scope' => $matches[1],
            'id' => (int) $matches[2],
        ];
    }

    private function validateMediaFiles(Tenant $sourceTenant, $media): void
    {
        foreach ($media as $item) {
            $directory = $this->mediaDirectory($sourceTenant, (int) $item->id);
            if (!File::isDirectory($directory)) {
                throw new RuntimeException(__('hiko.transfer_media_missing', ['id' => $item->id]));
            }
        }
    }

    private function copyIdentityRows($rows, string $prefix, array $letterMap, array $mappings): void
    {
        foreach ($rows as $row) {
            $data = (array) $row;
            unset($data['id']);
            $data['letter_id'] = $letterMap[(int) $row->letter_id];
            if ($row->identity_id) {
                $mapping = $mappings['identities'][(int) $row->identity_id];
                $data['identity_id'] = $mapping['id'];
                $data['global_identity_id'] = null;
            }
            DB::connection('mysql')->table("{$prefix}identity_letter")->insert($data);
        }
    }

    private function copyPlaceRows($rows, string $prefix, array $letterMap, array $mappings): void
    {
        foreach ($rows as $row) {
            $data = (array) $row;
            unset($data['id']);
            $data['letter_id'] = $letterMap[(int) $row->letter_id];
            if ($row->place_id) {
                $mapping = $mappings['places'][(int) $row->place_id];
                $data['place_id'] = $mapping['scope'] === 'local' ? $mapping['id'] : null;
                $data['global_place_id'] = $mapping['scope'] === 'global' ? $mapping['id'] : null;
            }
            DB::connection('mysql')->table("{$prefix}letter_place")->insert($data);
        }
    }

    private function copyKeywordRows($rows, string $prefix, array $letterMap, array $mappings): void
    {
        foreach ($rows as $row) {
            $data = (array) $row;
            unset($data['id']);
            $data['letter_id'] = $letterMap[(int) $row->letter_id];
            if ($row->keyword_id) {
                $mapping = $mappings['keywords'][(int) $row->keyword_id];
                $data['keyword_id'] = $mapping['scope'] === 'local' ? $mapping['id'] : null;
                $data['global_keyword_id'] = $mapping['scope'] === 'global' ? $mapping['id'] : null;
            }
            DB::connection('mysql')->table("{$prefix}keyword_letter")->insert($data);
        }
    }

    private function copyManifestations($rows, string $prefix, array $letterMap, array $mappings): void
    {
        foreach ($rows as $row) {
            $data = (array) $row;
            unset($data['id']);
            $data['letter_id'] = $letterMap[(int) $row->letter_id];

            $locationColumns = [
                'repository_id' => 'global_repository_id',
                'archive_id' => 'global_archive_id',
                'collection_id' => 'global_collection_id',
            ];
            foreach ($locationColumns as $column => $globalColumn) {
                if ($row->{$column}) {
                    $mapping = $mappings['locations'][(int) $row->{$column}];
                    $data[$column] = $mapping['scope'] === 'local' ? $mapping['id'] : null;
                    $data[$globalColumn] = $mapping['scope'] === 'global' ? $mapping['id'] : null;
                }
            }

            DB::connection('mysql')->table("{$prefix}manifestations")->insert($data);
        }
    }

    private function copyMedia(
        $media,
        Tenant $sourceTenant,
        Tenant $targetTenant,
        string $targetPrefix,
        array $letterMap,
        array &$createdDirectories
    ): void {
        foreach ($media as $item) {
            $data = (array) $item;
            unset($data['id']);
            $data['model_id'] = $letterMap[(int) $item->model_id];
            $targetMediaId = DB::connection('mysql')->table("{$targetPrefix}media")->insertGetId($data);
            $targetDirectory = $this->mediaDirectory($targetTenant, $targetMediaId);

            File::ensureDirectoryExists(dirname($targetDirectory));
            if (!File::copyDirectory($this->mediaDirectory($sourceTenant, (int) $item->id), $targetDirectory)) {
                throw new RuntimeException(__('hiko.transfer_media_copy_failed', ['id' => $item->id]));
            }

            $createdDirectories[] = $targetDirectory;
        }
    }

    private function mediaDirectory(Tenant $tenant, int $mediaId): string
    {
        return base_path("storage/{$tenant->table_prefix}/app/public/{$mediaId}");
    }

    private function snapshot(array $payload, array $mappings, array $result): array
    {
        $serialize = fn ($rows) => collect($rows)->map(fn ($row) => (array) $row)->values()->all();

        return [
            'captured_at' => now()->toIso8601String(),
            'letters' => $serialize($payload['letters']),
            'identity_rows' => $serialize($payload['identity_rows']),
            'place_rows' => $serialize($payload['place_rows']),
            'keyword_rows' => $serialize($payload['keyword_rows']),
            'manifestations' => $serialize($payload['manifestations']),
            'media' => $serialize($payload['media']),
            'global_dependencies' => collect($payload['global_dependencies'])
                ->map(fn ($rows) => $serialize($rows))
                ->all(),
            'mappings' => $mappings,
            'result' => $result,
        ];
    }
}
