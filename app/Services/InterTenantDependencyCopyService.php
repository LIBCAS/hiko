<?php

namespace App\Services;

use App\Enums\LocationType;
use App\Models\Country;
use App\Models\InterTenantTransferRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class InterTenantDependencyCopyService
{
    public function __construct(private InterTenantLetterTransferData $data)
    {
    }

    public function preview(
        InterTenantTransferRequest $transfer,
        Tenant $targetTenant,
        string $type,
        int $sourceId
    ): array {
        [$sourceTenant, $source] = $this->sourceDependency($transfer, $targetTenant, $type, $sourceId);

        return match ($type) {
            'places' => $this->previewPlace($source, $targetTenant),
            'locations' => $this->previewLocation($source, $targetTenant),
            'keywords' => $this->previewKeyword($sourceTenant, $source, $targetTenant),
        };
    }

    public function identityAutoMappings(array $payload, Tenant $targetTenant): array
    {
        $sourceIdentities = $payload['dependencies']['identities']
            ->filter(fn ($identity) => !empty($identity->global_identity_id));

        if ($sourceIdentities->isEmpty()) {
            return [];
        }

        $targetCandidates = DB::connection('mysql')
            ->table($targetTenant->table_prefix . '__identities')
            ->whereIn('global_identity_id', $sourceIdentities->pluck('global_identity_id')->unique())
            ->get(['id', 'type', 'global_identity_id'])
            ->groupBy(fn ($identity) => $identity->global_identity_id . ':' . $identity->type);

        return $sourceIdentities->mapWithKeys(function ($source) use ($targetCandidates) {
            $matches = $targetCandidates->get($source->global_identity_id . ':' . $source->type, collect());

            return $matches->count() === 1
                ? [(int) $source->id => 'local-' . $matches->first()->id]
                : [];
        })->all();
    }

    public function copy(
        InterTenantTransferRequest $transfer,
        Tenant $targetTenant,
        User $user,
        string $type,
        int $sourceId,
        ?int $categoryId = null
    ): array {
        [$sourceTenant, $source] = $this->sourceDependency($transfer, $targetTenant, $type, $sourceId);

        return DB::connection('mysql')->transaction(function () use (
            $transfer,
            $targetTenant,
            $user,
            $type,
            $sourceId,
            $sourceTenant,
            $source,
            $categoryId
        ) {
            $locked = InterTenantTransferRequest::query()
                ->whereKey($transfer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$locked->isPending()) {
                throw new RuntimeException(__('hiko.transfer_not_approvable'));
            }

            $result = match ($type) {
                'places' => $this->copyPlace($source, $targetTenant),
                'locations' => $this->copyLocation($source, $targetTenant),
                'keywords' => $this->copyKeyword($sourceTenant, $source, $targetTenant, $categoryId),
            };

            $audit = $locked->result ?? [];
            $audit['dependency_copies'][] = [
                'created_at' => now()->toIso8601String(),
                'created_by_user_id' => $user->id,
                'created_by_name' => $user->name,
                'type' => $type,
                'source_id' => $sourceId,
                'target_scope' => 'local',
                'target_id' => $result['id'],
                'action' => $result['action'],
                'category_action' => $result['category_action'] ?? null,
                'category_id' => $result['category_id'] ?? null,
            ];
            $locked->update(['result' => $audit]);

            return $result;
        });
    }

    private function sourceDependency(
        InterTenantTransferRequest $transfer,
        Tenant $targetTenant,
        string $type,
        int $sourceId
    ): array {
        if (!in_array($type, ['places', 'keywords', 'locations'], true)) {
            throw new RuntimeException(__('hiko.transfer_dependency_type_invalid'));
        }

        if (!$transfer->isPending() || (int) $transfer->target_tenant_id !== (int) $targetTenant->id) {
            throw new RuntimeException(__('hiko.transfer_not_approvable'));
        }

        $sourceTenant = $transfer->sourceTenant()->firstOrFail();
        $payload = $this->data->load($sourceTenant, $transfer->source_record_ids);
        $source = $payload['dependencies'][$type]->firstWhere('id', $sourceId);

        if (!$source) {
            throw new RuntimeException(__('hiko.transfer_dependency_not_found'));
        }

        return [$sourceTenant, $source];
    }

    private function previewPlace(object $source, Tenant $targetTenant): array
    {
        $attributes = $this->placeAttributes($source);
        $matches = $this->placeMatches($attributes, $targetTenant);
        $this->assertAtMostOneMatch($matches);

        return [
            'action' => $matches->isEmpty() ? 'create' : 'reuse',
            'message' => $matches->isEmpty()
                ? __('hiko.transfer_copy_place_create_preview')
                : null,
            'message_parts' => $matches->isNotEmpty()
                ? $this->reuseMessageParts(
                    'hiko.transfer_copy_place_reuse_preview_before',
                    'hiko.transfer_copy_place_reuse_preview_after',
                    'places.edit',
                    (int) $matches->first()->id
                )
                : null,
        ];
    }

    private function copyPlace(object $source, Tenant $targetTenant): array
    {
        $attributes = $this->placeAttributes($source);
        $matches = $this->placeMatches($attributes, $targetTenant);
        $this->assertAtMostOneMatch($matches);

        if ($matches->isNotEmpty()) {
            return ['id' => (int) $matches->first()->id, 'action' => 'reused'];
        }

        $attributes = array_merge($attributes, [
            'alternative_names' => $attributes['alternative_names'] === null
                ? null
                : json_encode(
                    $attributes['alternative_names'],
                    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                ),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = DB::connection('mysql')
            ->table($targetTenant->table_prefix . '__places')
            ->insertGetId($attributes);

        return ['id' => $id, 'action' => 'created'];
    }

    private function placeMatches(array $source, Tenant $targetTenant): Collection
    {
        $table = $targetTenant->table_prefix . '__places';

        return DB::connection('mysql')->table($table)
            ->where(function ($query) use ($source) {
                $query->where(function ($names) use ($source) {
                    $names->whereRaw('LOWER(name) = ?', [mb_strtolower($source['name'])])
                        ->whereRaw('LOWER(COALESCE(country, "")) = ?', [mb_strtolower($source['country'])])
                        ->whereRaw('LOWER(COALESCE(division, "")) = ?', [mb_strtolower((string) $source['division'])]);
                });

                if ($source['latitude'] !== null && $source['longitude'] !== null) {
                    $query->orWhere(fn ($coordinates) => $coordinates
                        ->where('latitude', $source['latitude'])
                        ->where('longitude', $source['longitude']));
                }

                if ($source['geoname_id'] !== null) {
                    $query->orWhere('geoname_id', $source['geoname_id']);
                }
            })
            ->get();
    }

    private function previewLocation(object $source, Tenant $targetTenant): array
    {
        $attributes = $this->locationAttributes($source);
        $matches = $this->locationMatches($attributes, $targetTenant);
        $this->assertAtMostOneMatch($matches);

        return [
            'action' => $matches->isEmpty() ? 'create' : 'reuse',
            'message' => $matches->isEmpty()
                ? __('hiko.transfer_copy_location_create_preview')
                : null,
            'message_parts' => $matches->isNotEmpty()
                ? $this->reuseMessageParts(
                    'hiko.transfer_copy_location_reuse_preview_before',
                    'hiko.transfer_copy_location_reuse_preview_after',
                    'locations.edit',
                    (int) $matches->first()->id
                )
                : null,
        ];
    }

    private function copyLocation(object $source, Tenant $targetTenant): array
    {
        $attributes = $this->locationAttributes($source);
        $matches = $this->locationMatches($attributes, $targetTenant);
        $this->assertAtMostOneMatch($matches);

        if ($matches->isNotEmpty()) {
            return ['id' => (int) $matches->first()->id, 'action' => 'reused'];
        }

        $id = DB::connection('mysql')
            ->table($targetTenant->table_prefix . '__locations')
            ->insertGetId([
                'name' => $attributes['name'],
                'type' => $attributes['type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return ['id' => $id, 'action' => 'created'];
    }

    private function locationMatches(array $source, Tenant $targetTenant): Collection
    {
        return DB::connection('mysql')
            ->table($targetTenant->table_prefix . '__locations')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($source['name'])])
            ->where('type', $source['type'])
            ->get();
    }

    private function previewKeyword(Tenant $sourceTenant, object $source, Tenant $targetTenant): array
    {
        $categoryRow = $this->sourceKeywordCategory($sourceTenant, $source);
        $source = (object) $this->translatedNameAttributes($source->name);
        $category = (object) $this->translatedNameAttributes(
            $categoryRow->name
        );
        $categoryMatches = $this->categoryMatches($category, $targetTenant);

        if ($categoryMatches->count() > 1) {
            return [
                'action' => 'choose_category',
                'message' => __('hiko.transfer_copy_keyword_choose_category'),
                'category_options' => $categoryMatches->map(fn ($item) => [
                    'id' => (int) $item->id,
                    'label' => $this->translatedName($item->name),
                ])->values()->all(),
            ];
        }

        $categoryAction = $categoryMatches->isEmpty() ? 'create' : 'reuse';
        $categoryId = $categoryMatches->first()?->id;
        $keywordMatches = $categoryId
            ? $this->keywordMatches($source, $targetTenant, (int) $categoryId)
            : collect();
        $this->assertAtMostOneMatch($keywordMatches);

        if ($keywordMatches->isNotEmpty()) {
            $keywordId = (int) $keywordMatches->first()->id;

            return [
                'action' => 'reuse',
                'category_action' => 'reuse',
                'message' => null,
                'message_parts' => [
                    ['type' => 'text', 'text' => __('hiko.transfer_copy_keyword_reuse_preview_before')],
                    $this->linkMessagePart('keywords.edit', $keywordId),
                    ['type' => 'text', 'text' => __('hiko.transfer_copy_keyword_reuse_preview_between')],
                    $this->linkMessagePart('keywords.category.edit', (int) $categoryId),
                    ['type' => 'text', 'text' => __('hiko.transfer_copy_keyword_reuse_preview_after')],
                ],
            ];
        }

        if ($categoryAction === 'reuse') {
            return [
                'action' => 'create',
                'category_action' => 'reuse',
                'message' => null,
                'message_parts' => [
                    ['type' => 'text', 'text' => __('hiko.transfer_copy_keyword_create_existing_category_before')],
                    $this->linkMessagePart('keywords.category.edit', (int) $categoryId),
                    ['type' => 'text', 'text' => __('hiko.transfer_copy_keyword_create_existing_category_after')],
                ],
            ];
        }

        return [
            'action' => 'create',
            'category_action' => $categoryAction,
            'message' => __('hiko.transfer_copy_keyword_create_with_category_preview'),
        ];
    }

    private function copyKeyword(
        Tenant $sourceTenant,
        object $source,
        Tenant $targetTenant,
        ?int $selectedCategoryId
    ): array {
        $categoryRow = $this->sourceKeywordCategory($sourceTenant, $source);
        $source = (object) $this->translatedNameAttributes($source->name);
        $category = (object) $this->translatedNameAttributes($categoryRow->name);
        $categoryMatches = $this->categoryMatches($category, $targetTenant);

        if ($categoryMatches->count() > 1) {
            if (!$selectedCategoryId || !$categoryMatches->contains('id', $selectedCategoryId)) {
                throw new RuntimeException(__('hiko.transfer_copy_keyword_category_required'));
            }
            $categoryId = $selectedCategoryId;
            $categoryAction = 'reused';
        } elseif ($categoryMatches->count() === 1) {
            $categoryId = (int) $categoryMatches->first()->id;
            $categoryAction = 'reused';
        } else {
            $categoryId = DB::connection('mysql')
                ->table($targetTenant->table_prefix . '__keyword_categories')
                ->insertGetId([
                    'name' => $category->name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            $categoryAction = 'created';
        }

        $keywordMatches = $this->keywordMatches($source, $targetTenant, $categoryId);
        $this->assertAtMostOneMatch($keywordMatches);

        if ($keywordMatches->isNotEmpty()) {
            return [
                'id' => (int) $keywordMatches->first()->id,
                'action' => 'reused',
                'category_action' => $categoryAction,
                'category_id' => $categoryId,
            ];
        }

        $id = DB::connection('mysql')
            ->table($targetTenant->table_prefix . '__keywords')
            ->insertGetId([
                'name' => $source->name,
                'keyword_category_id' => $categoryId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return [
            'id' => $id,
            'action' => 'created',
            'category_action' => $categoryAction,
            'category_id' => $categoryId,
        ];
    }

    private function sourceKeywordCategory(Tenant $sourceTenant, object $source): object
    {
        if (empty($source->keyword_category_id)) {
            throw new RuntimeException(__('hiko.transfer_copy_keyword_category_missing'));
        }

        $category = DB::connection('mysql')
            ->table($sourceTenant->table_prefix . '__keyword_categories')
            ->find($source->keyword_category_id);

        if (!$category) {
            throw new RuntimeException(__('hiko.transfer_copy_keyword_category_missing'));
        }

        return $category;
    }

    private function categoryMatches(object $sourceCategory, Tenant $targetTenant): Collection
    {
        $sourceName = $this->normalizedTranslations($sourceCategory->name);

        return DB::connection('mysql')
            ->table($targetTenant->table_prefix . '__keyword_categories')
            ->get()
            ->filter(fn ($category) => $this->normalizedTranslations($category->name) === $sourceName)
            ->values();
    }

    private function keywordMatches(object $source, Tenant $targetTenant, int $categoryId): Collection
    {
        $sourceName = $this->normalizedTranslations($source->name);

        return DB::connection('mysql')
            ->table($targetTenant->table_prefix . '__keywords')
            ->where('keyword_category_id', $categoryId)
            ->get()
            ->filter(fn ($keyword) => $this->normalizedTranslations($keyword->name) === $sourceName)
            ->values();
    }

    private function normalizedTranslations(string $json): array
    {
        $translations = json_decode($json, true) ?: [];

        return [
            'cs' => mb_strtolower(trim((string) ($translations['cs'] ?? ''))),
            'en' => mb_strtolower(trim((string) ($translations['en'] ?? ''))),
        ];
    }

    private function placeAttributes(object $source): array
    {
        $alternativeNames = $source->alternative_names ?? [];
        if (is_string($alternativeNames)) {
            $alternativeNames = json_decode($alternativeNames, true);
        }

        $attributes = [
            'name' => trim((string) ($source->name ?? '')),
            'additional_name' => $this->nullableTrim($source->additional_name ?? null),
            'country' => trim((string) ($source->country ?? '')),
            'division' => $this->nullableTrim($source->division ?? null),
            'note' => $source->note ?? null,
            'latitude' => $source->latitude ?? null,
            'longitude' => $source->longitude ?? null,
            'alternative_names' => $alternativeNames,
            'geoname_id' => $source->geoname_id ?? null,
        ];

        return $this->validated($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'additional_name' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255', Rule::in(Country::names())],
            'division' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'alternative_names' => ['nullable', 'array'],
            'geoname_id' => ['nullable', 'integer'],
        ]);
    }

    private function locationAttributes(object $source): array
    {
        return $this->validated([
            'name' => trim((string) ($source->name ?? '')),
            'type' => trim((string) ($source->type ?? '')),
        ], [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(LocationType::values())],
        ]);
    }

    private function translatedNameAttributes(string $json): array
    {
        $translations = json_decode($json, true);
        $attributes = [
            'cs' => $this->nullableTrim(is_array($translations) ? ($translations['cs'] ?? null) : null),
            'en' => $this->nullableTrim(is_array($translations) ? ($translations['en'] ?? null) : null),
        ];
        $validated = $this->validated($attributes, [
            'cs' => ['nullable', 'string', 'max:255', 'required_without:en'],
            'en' => ['nullable', 'string', 'max:255', 'required_without:cs'],
        ]);

        return [
            'name' => json_encode($validated, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ];
    }

    private function validated(array $attributes, array $rules): array
    {
        $validator = Validator::make($attributes, $rules);

        if ($validator->fails()) {
            throw new RuntimeException($validator->errors()->first());
        }

        return $validator->validated();
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }

    private function translatedName(string $json): string
    {
        $translations = json_decode($json, true) ?: [];

        return $translations[app()->getLocale()]
            ?? $translations['cs']
            ?? $translations['en']
            ?? $json;
    }

    private function reuseMessageParts(
        string $beforeTranslation,
        string $afterTranslation,
        string $route,
        int $id
    ): array {
        return [
            ['type' => 'text', 'text' => __($beforeTranslation)],
            $this->linkMessagePart($route, $id),
            ['type' => 'text', 'text' => __($afterTranslation)],
        ];
    }

    private function linkMessagePart(string $route, int $id): array
    {
        return [
            'type' => 'link',
            'text' => '#' . $id,
            'url' => route($route, $id),
        ];
    }

    private function assertAtMostOneMatch(Collection $matches): void
    {
        if ($matches->count() > 1) {
            throw new RuntimeException(__('hiko.transfer_copy_multiple_matches'));
        }
    }
}
