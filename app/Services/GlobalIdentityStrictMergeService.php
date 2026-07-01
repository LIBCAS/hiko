<?php

namespace App\Services;

use App\Models\GlobalIdentity;
use App\Models\MergeAuditLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GlobalIdentityStrictMergeService
{
    public const PERSON_FIELDS = [
        'surname',
        'forename',
        'general_name_modifier',
        'related_names',
        'type',
        'nationality',
        'gender',
        'birth_year',
        'death_year',
        'related_identity_resources',
        'religions',
        'professions',
        'viaf_id',
        'note',
    ];

    public const INSTITUTION_FIELDS = [
        'name',
        'type',
        'related_identity_resources',
        'note',
    ];

    public const SCALAR_FIELDS = [
        'name',
        'surname',
        'forename',
        'general_name_modifier',
        'type',
        'gender',
        'birth_year',
        'death_year',
        'viaf_id',
    ];

    public const MULTI_FIELDS = [
        'related_names',
        'nationality',
        'related_identity_resources',
        'religions',
        'professions',
        'note',
    ];

    public function getSelectionQuery(array $filters = [])
    {
        $query = GlobalIdentity::query()
            ->with(['professions.profession_category'])
            ->select([
                'id',
                'name',
                'surname',
                'forename',
                'type',
                'nationality',
                'gender',
                'birth_year',
                'death_year',
                'related_names',
                'viaf_id',
                'admin_notes',
            ]);

        $ids = $this->parseIdFilter($filters['ids'] ?? '');
        if ($ids !== []) {
            $query->whereIn('id', $ids);
        }

        if (!empty($filters['name'])) {
            $term = trim((string)$filters['name']);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('surname', 'like', '%' . $term . '%')
                    ->orWhere('forename', 'like', '%' . $term . '%');
            });
        }

        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['admin_notes'])) {
            $term = trim((string)$filters['admin_notes']);
            $query->where('admin_notes', 'like', '%' . $term . '%');
        }

        if (!empty($filters['duplicates_only'])) {
            $query->whereExists(function ($duplicates) {
                $duplicates
                    ->selectRaw('1')
                    ->from('global_identities as duplicate_global_identities')
                    ->whereColumn('duplicate_global_identities.name', 'global_identities.name')
                    ->whereColumn('duplicate_global_identities.type', 'global_identities.type')
                    ->whereColumn('duplicate_global_identities.id', '<>', 'global_identities.id');
            });
        }

        return $query->orderBy('name')->orderBy('id');
    }

    private function parseIdFilter(mixed $value): array
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        return collect(preg_split('/[^0-9]+/', (string)$value) ?: [])
            ->map(fn($id) => (int)$id)
            ->filter(fn(int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function adminNoteReferences(string|null $adminNotes): array
    {
        return collect(explode(',', (string)$adminNotes))
            ->map(fn($note) => trim($note))
            ->filter(fn($note) => preg_match('/^[A-Za-z0-9_-]+#\d+$/', $note) === 1)
            ->map(function ($note) {
                [$tenantKey, $id] = explode('#', $note, 2);

                return [
                    'reference' => $note,
                    'tenant_key' => $tenantKey,
                    'id' => (int)$id,
                    'url' => "https://{$tenantKey}.historicka-korespondence.cz/identities/{$id}/edit",
                ];
            })
            ->values()
            ->all();
    }

    public function formatAdminNotes(string|null $adminNotes): string
    {
        if (is_null($adminNotes) || trim((string)$adminNotes) === '') {
            return '—';
        }

        if (!str_contains($adminNotes, '#')) {
            return e($adminNotes);
        }

        $items = collect($this->adminNoteReferences($adminNotes))
            ->map(function (array $reference) {
                return '<li><a href="' . e($reference['url']) . '" target="_blank" class="text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark">' . e($reference['reference']) . '</a></li>';
            })
            ->implode('');

        return '<ul class="list-disc list-inside text-gray-600 space-y-1">' . $items . '</ul>';
    }

    public function getLocalIdentityPreview(string $reference): ?array
    {
        if (preg_match('/^([A-Za-z0-9_-]+)#(\d+)$/', trim($reference), $matches) !== 1) {
            return null;
        }

        $tenantKey = $matches[1];
        $identityId = (int)$matches[2];
        $tenantExists = Schema::hasTable('tenants')
            && DB::table('tenants')->where('table_prefix', $tenantKey)->exists();
        $table = "{$tenantKey}__identities";

        if (!$tenantExists || !Schema::hasTable($table)) {
            return null;
        }

        $columns = collect([
            'id',
            'name',
            'surname',
            'forename',
            'general_name_modifier',
            'alternative_names',
            'related_names',
            'type',
            'nationality',
            'gender',
            'birth_year',
            'death_year',
            'related_identity_resources',
            'viaf_id',
            'note',
        ])->filter(fn(string $column) => Schema::hasColumn($table, $column))->all();

        $identity = DB::table($table)->select($columns)->where('id', $identityId)->first();
        if (!$identity) {
            return null;
        }

        $data = (array)$identity;
        foreach (['alternative_names', 'related_names', 'related_identity_resources'] as $field) {
            $data[$field] = $this->decodeJsonValue($data[$field] ?? null);
        }

        $data['reference'] = "{$tenantKey}#{$identityId}";
        $data['tenant_key'] = $tenantKey;
        $data['edit_url'] = "https://{$tenantKey}.historicka-korespondence.cz/identities/{$identityId}/edit";

        return $data;
    }

    public function getPreviewRecords(array $ids): Collection
    {
        $ids = $this->normalizeIds($ids);

        if (count($ids) < 2) {
            return collect();
        }

        return GlobalIdentity::query()
            ->with(['professions.profession_category', 'religions'])
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();
    }

    public function hasSingleType(Collection $records): bool
    {
        return $records->pluck('type')->unique()->count() === 1;
    }

    public function fieldsForType(?string $type): array
    {
        return $type === 'institution' ? self::INSTITUTION_FIELDS : self::PERSON_FIELDS;
    }

    public function scalarOptions(Collection $records, string $field): array
    {
        $options = [];

        foreach ($records as $record) {
            $key = $this->valueKey($record->getAttribute($field));
            if (!isset($options[$key])) {
                $options[$key] = [
                    'key' => $key,
                    'ids' => [],
                    'value' => $record->getAttribute($field),
                    'label' => $this->formatPlainValue($record->getAttribute($field)),
                ];
            }

            $options[$key]['ids'][] = (int)$record->id;
        }

        return array_values($options);
    }

    public function multiOptions(Collection $records, string $field): array
    {
        $options = [];

        foreach ($records as $record) {
            foreach ($this->itemsForField($record, $field) as $item) {
                $key = $this->valueKey($item['value']);

                if (!isset($options[$key])) {
                    $options[$key] = [
                        'key' => $key,
                        'ids' => [],
                        'value' => $item['value'],
                        'label' => $item['label'],
                        'html' => $item['html'] ?? e($item['label']),
                    ];
                }

                $options[$key]['ids'][] = (int)$record->id;
            }
        }

        foreach ($options as &$option) {
            $option['ids'] = array_values(array_unique($option['ids']));
            sort($option['ids']);
        }

        return array_values($options);
    }

    public function survivorScalarDefaults(Collection $records, int $survivorId): array
    {
        $survivor = $records->firstWhere('id', $survivorId) ?? $records->first();
        $defaults = [];

        if (!$survivor) {
            return $defaults;
        }

        foreach ($this->fieldsForType($survivor->type) as $field) {
            if (in_array($field, self::SCALAR_FIELDS, true)) {
                $defaults[$field] = $this->valueKey($survivor->getAttribute($field));
            }
        }

        return $defaults;
    }

    public function defaultMultiSelections(Collection $records): array
    {
        $type = $records->first()?->type;
        $defaults = [];

        foreach ($this->fieldsForType($type) as $field) {
            if (in_array($field, self::MULTI_FIELDS, true)) {
                $defaults[$field] = collect($this->multiOptions($records, $field))->pluck('key')->values()->all();
            }
        }

        return $defaults;
    }

    public function finalPreviewValue(Collection $records, string $field, array $scalarSelections, array $multiSelections): string
    {
        $value = $this->resolveFieldValue($records, $field, $scalarSelections, $multiSelections);

        if ($field === 'professions') {
            return $this->formatProfessionsHtml(collect($value));
        }

        if ($field === 'religions') {
            return $this->formatReligionIdsHtml(collect($value)->map(fn($id) => (int)$id)->all());
        }

        if (in_array($field, ['related_names', 'related_identity_resources'], true)) {
            return $this->formatJsonItemsHtml($value, $field);
        }

        if ($field === 'note') {
            return nl2br(e((string)$value));
        }

        return e($this->formatPlainValue($value));
    }

    public function execute(array $ids, int $survivorId, array $scalarSelections, array $multiSelections): array
    {
        $ids = $this->normalizeIds($ids);

        if (count($ids) < 2) {
            throw new \InvalidArgumentException(__('hiko.strict_global_merge_select_at_least_two'));
        }

        if (!in_array($survivorId, $ids, true)) {
            throw new \InvalidArgumentException('Survivor identity must be one of the selected identities.');
        }

        $auditPayload = [
            'ids' => $ids,
            'survivor_id' => $survivorId,
            'scalar_selections' => $scalarSelections,
            'multi_selections' => $multiSelections,
        ];
        $auditResult = [];

        try {
            DB::transaction(function () use ($ids, $survivorId, $scalarSelections, $multiSelections, &$auditResult) {
                $records = GlobalIdentity::query()
                    ->with(['professions.profession_category', 'religions'])
                    ->whereIn('id', $ids)
                    ->orderBy('id')
                    ->get();

                if ($records->count() !== count($ids)) {
                    throw new \InvalidArgumentException('One or more selected global identities no longer exist.');
                }

                if (!$this->hasSingleType($records)) {
                    throw new \InvalidArgumentException(__('hiko.strict_global_merge_mixed_types_error'));
                }

                $survivor = $records->firstWhere('id', $survivorId);
                $finalType = $this->resolveFieldValue($records, 'type', $scalarSelections, $multiSelections);

                if ($finalType !== $survivor->type || $records->contains(fn($record) => $record->type !== $finalType)) {
                    throw new \InvalidArgumentException(__('hiko.strict_global_merge_mixed_types_error'));
                }

                $loserIds = array_values(array_diff($ids, [$survivorId]));
                $fields = $this->fieldsForType($finalType);
                $updateData = [];

                foreach ($fields as $field) {
                    if (in_array($field, ['religions', 'professions'], true)) {
                        continue;
                    }

                    $updateData[$field] = $this->resolveFieldValue($records, $field, $scalarSelections, $multiSelections);
                }

                $alternativeNames = $this->concatenateAlternativeNames($records);

                if ($finalType === 'person') {
                    $surname = trim((string)($updateData['surname'] ?? ''));
                    $forename = trim((string)($updateData['forename'] ?? ''));
                    if ($surname === '') {
                        throw new \InvalidArgumentException(__('validation.required', ['attribute' => __('hiko.surname')]));
                    }

                    $updateData['name'] = $surname . ($forename !== '' ? ", {$forename}" : '');
                } elseif (trim((string)($updateData['name'] ?? '')) === '') {
                    throw new \InvalidArgumentException(__('validation.required', ['attribute' => __('hiko.name')]));
                }

                $updateData['admin_notes'] = $this->concatenateAdminNotes(
                    $records->pluck('admin_notes')->filter(fn($value) => trim((string)$value) !== '')->all()
                );

                $survivor->update($updateData);
                DB::table('global_identities')
                    ->where('id', $survivorId)
                    ->update([
                        'alternative_names' => $alternativeNames,
                        'updated_at' => now(),
                    ]);

                if (in_array('professions', $fields, true)) {
                    $this->syncProfessions($records, $survivorId, $multiSelections['professions'] ?? []);
                } else {
                    $this->deleteProfessions($loserIds);
                }

                if (in_array('religions', $fields, true)) {
                    $this->syncReligions($records, $survivorId, $multiSelections['religions'] ?? []);
                } else {
                    $this->deleteReligions($loserIds);
                }

                $this->moveKeywords($loserIds, $survivorId);
                $tenantUpdates = $this->repointTenantReferences($loserIds, $survivorId);

                GlobalIdentity::query()
                    ->whereIn('id', $loserIds)
                    ->delete();

                $auditResult = [
                    'survivor_id' => $survivorId,
                    'deleted_ids' => $loserIds,
                    'tenant_updates' => $tenantUpdates,
                ];
            });

            $this->logAudit('success', $auditPayload, $auditResult);
            Log::info('[GlobalIdentityStrictMerge] success', $auditResult);

            return [
                'success' => true,
                'survivor_id' => $survivorId,
                'deleted_ids' => array_values(array_diff($ids, [$survivorId])),
            ];
        } catch (\Throwable $e) {
            $this->logAudit('error', $auditPayload, $auditResult, $e->getMessage());
            Log::error('[GlobalIdentityStrictMerge] error: ' . $e->getMessage(), [
                'payload' => $auditPayload,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function resolveFieldValue(Collection $records, string $field, array $scalarSelections, array $multiSelections)
    {
        if (in_array($field, self::SCALAR_FIELDS, true)) {
            $options = collect($this->scalarOptions($records, $field))->keyBy('key');
            $selectedKey = $scalarSelections[$field] ?? $options->keys()->first();

            return $options->get($selectedKey, $options->first())['value'] ?? null;
        }

        $options = collect($this->multiOptions($records, $field))->keyBy('key');
        $selectedKeys = $multiSelections[$field] ?? [];

        if ($field === 'nationality') {
            return $this->formatNationalities(collect($selectedKeys)
                ->map(fn($key) => $options->get($key)['value'] ?? null)
                ->filter()
                ->all());
        }

        if ($field === 'note') {
            return $this->concatenateUnique(
                collect($selectedKeys)->map(fn($key) => $options->get($key)['value'] ?? null)->filter()->all(),
                "\n\n===\n\n"
            );
        }

        return collect($selectedKeys)
            ->map(fn($key) => $options->get($key)['value'] ?? null)
            ->filter(fn($value) => $value !== null && $value !== '')
            ->values()
            ->all();
    }

    private function itemsForField(GlobalIdentity $record, string $field): array
    {
        if ($field === 'note') {
            $value = trim((string)$record->getAttribute($field));
            return $value === '' ? [] : [[
                'value' => $value,
                'label' => $value,
            ]];
        }

        if ($field === 'nationality') {
            return collect($this->splitNationalities($record->nationality))
                ->map(fn(string $nationality): array => [
                    'value' => $nationality,
                    'label' => $nationality,
                ])
                ->values()
                ->all();
        }

        if ($field === 'related_names' || $field === 'related_identity_resources') {
            return collect($record->getAttribute($field) ?? [])
                ->filter(fn($value) => is_array($value))
                ->map(fn(array $value): array => [
                    'value' => $value,
                    'label' => $this->formatJsonItemLabel($value, $field),
                    'html' => $this->formatJsonItemHtml($value, $field),
                ])
                ->values()
                ->all();
        }

        if ($field === 'religions') {
            $religionLabels = $this->religionLabels($record->religions->pluck('id')->all());

            return $record->religions
                ->map(fn($religion): array => [
                    'value' => (int)$religion->id,
                    'label' => $religionLabels[(int)$religion->id] ?? $religion->name,
                ])
                ->values()
                ->all();
        }

        if ($field === 'professions') {
            return $record->professions
                ->map(fn($profession): array => [
                    'value' => (int)$profession->id,
                    'label' => $profession->getTranslation('name', app()->getLocale()),
                    'html' => $this->formatProfessionHtml($profession),
                ])
                ->values()
                ->all();
        }

        return [];
    }

    private function syncProfessions(Collection $records, int $survivorId, array $selectedKeys): void
    {
        $options = collect($this->multiOptions($records, 'professions'))->keyBy('key');
        $ids = collect($selectedKeys)
            ->map(fn($key) => $options->get($key)['value'] ?? null)
            ->filter()
            ->map(fn($id) => (int)$id)
            ->unique()
            ->values();

        DB::table('global_identity_profession')
            ->whereIn('global_identity_id', $records->pluck('id')->all())
            ->delete();

        foreach ($ids as $position => $professionId) {
            DB::table('global_identity_profession')->insertOrIgnore([
                'global_identity_id' => $survivorId,
                'global_profession_id' => $professionId,
                'position' => $position + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function syncReligions(Collection $records, int $survivorId, array $selectedKeys): void
    {
        $options = collect($this->multiOptions($records, 'religions'))->keyBy('key');
        $ids = collect($selectedKeys)
            ->map(fn($key) => $options->get($key)['value'] ?? null)
            ->filter()
            ->map(fn($id) => (int)$id)
            ->unique()
            ->values();

        DB::table('global_identity_religion')
            ->whereIn('global_identity_id', $records->pluck('id')->all())
            ->delete();

        foreach ($ids as $religionId) {
            DB::table('global_identity_religion')->insertOrIgnore([
                'global_identity_id' => $survivorId,
                'religion_id' => $religionId,
            ]);
        }
    }

    private function deleteProfessions(array $ids): void
    {
        DB::table('global_identity_profession')->whereIn('global_identity_id', $ids)->delete();
    }

    private function deleteReligions(array $ids): void
    {
        DB::table('global_identity_religion')->whereIn('global_identity_id', $ids)->delete();
    }

    private function moveKeywords(array $loserIds, int $survivorId): void
    {
        if (!Schema::hasTable('global_identity_keyword')) {
            return;
        }

        $records = DB::table('global_identity_keyword')
            ->whereIn('identity_id', $loserIds)
            ->get();

        foreach ($records as $record) {
            DB::table('global_identity_keyword')->insertOrIgnore([
                'identity_id' => $survivorId,
                'keyword_id' => $record->keyword_id,
                'created_at' => $record->created_at,
                'updated_at' => now(),
            ]);
        }

        DB::table('global_identity_keyword')
            ->whereIn('identity_id', $loserIds)
            ->delete();
    }

    private function repointTenantReferences(array $loserIds, int $survivorId): array
    {
        $updates = [];

        foreach (DB::table('tenants')->pluck('table_prefix') as $prefix) {
            $identityTable = "{$prefix}__identities";
            $identityLetterTable = "{$prefix}__identity_letter";
            $updates[$prefix] = [
                'identities' => 0,
                'identity_letter' => 0,
            ];

            if (Schema::hasTable($identityTable)) {
                $updates[$prefix]['identities'] = DB::table($identityTable)
                    ->whereIn('global_identity_id', $loserIds)
                    ->update(['global_identity_id' => $survivorId]);
            }

            if (Schema::hasTable($identityLetterTable)) {
                $updates[$prefix]['identity_letter'] = DB::table($identityLetterTable)
                    ->whereIn('global_identity_id', $loserIds)
                    ->update(['global_identity_id' => $survivorId]);
            }
        }

        return $updates;
    }

    public function formatIds(array $ids): string
    {
        sort($ids);

        return collect($ids)
            ->map(fn($id) => '<a href="' . route('global.identities.edit', $id) . '" target="_blank" class="text-primary hover:underline font-mono">' . (int)$id . '</a>')
            ->implode(', ');
    }

    private function formatJsonItemsHtml($items, string $field): string
    {
        $items = collect($items)->filter(fn($item) => is_array($item));

        if ($items->isEmpty()) {
            return '—';
        }

        return '<ul class="list-disc list-inside text-gray-600 space-y-1">'
            . $items->map(fn($item) => '<li>' . $this->formatJsonItemHtml((array)$item, $field) . '</li>')->implode('')
            . '</ul>';
    }

    private function formatListHtml(array $items): string
    {
        $items = collect($items)->filter(fn($item) => trim((string)$item) !== '');

        if ($items->isEmpty()) {
            return '—';
        }

        return '<ul class="list-disc list-inside text-gray-600 space-y-1">'
            . $items->map(fn($item) => '<li>' . e((string)$item) . '</li>')->implode('')
            . '</ul>';
    }

    private function formatProfessionsHtml(Collection $ids): string
    {
        if ($ids->isEmpty()) {
            return '—';
        }

        $professions = DB::table('global_professions')
            ->leftJoin('global_profession_categories', 'global_professions.profession_category_id', '=', 'global_profession_categories.id')
            ->whereIn('global_professions.id', $ids->all())
            ->select(
                'global_professions.id',
                'global_professions.name',
                'global_profession_categories.id as category_id',
                'global_profession_categories.name as category_name'
            )
            ->orderBy('global_professions.id')
            ->get();

        return '<ul class="list-disc list-inside text-gray-600 space-y-1">'
            . $professions->map(function ($profession) {
                return '<li>' . $this->formatProfessionRowHtml($profession) . '</li>';
            })->implode('')
            . '</ul>';
    }

    private function formatProfessionHtml($profession): string
    {
        $locale = app()->getLocale();
        $name = e($profession->getTranslation('name', $locale));
        $category = $profession->profession_category;
        $html = '<a href="' . route('global.professions.edit', $profession->id) . '" target="_blank" class="text-primary hover:underline">' . $name . '</a>';

        if ($category) {
            $html .= ' | <a href="' . route('global.professions.category.edit', $category->id) . '" target="_blank" class="text-xs text-primary-dark border-b border-primary-light hover:border-primary-dark">' . e($category->getTranslation('name', $locale)) . '</a>';
        }

        return $html;
    }

    private function formatProfessionRowHtml($profession): string
    {
        $name = $this->jsonTranslation((string)$profession->name);
        $html = '<a href="' . route('global.professions.edit', $profession->id) . '" target="_blank" class="text-primary hover:underline">' . e($name) . '</a>';

        if ($profession->category_id) {
            $html .= ' | <a href="' . route('global.professions.category.edit', $profession->category_id) . '" target="_blank" class="text-xs text-primary-dark border-b border-primary-light hover:border-primary-dark">' . e($this->jsonTranslation((string)$profession->category_name)) . '</a>';
        }

        return $html;
    }

    private function formatJsonItemLabel(array $value, string $field): string
    {
        if ($field === 'related_names') {
            return trim(implode(' ', array_filter([
                $value['surname'] ?? '',
                $value['forename'] ?? '',
                $value['general_name_modifier'] ?? '',
            ], fn($part) => trim((string)$part) !== ''))) ?: json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($field === 'related_identity_resources') {
            $title = trim((string)($value['title'] ?? ''));
            $link = trim((string)($value['link'] ?? ''));
            return trim($title . ($link !== '' ? " ({$link})" : '')) ?: json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    private function formatJsonItemHtml(array $value, string $field): string
    {
        if ($field !== 'related_identity_resources') {
            return e($this->formatJsonItemLabel($value, $field));
        }

        $title = trim((string)($value['title'] ?? ''));
        $link = trim((string)($value['link'] ?? ''));

        if ($title === '') {
            return e($this->formatJsonItemLabel($value, $field));
        }

        if ($link === '') {
            return e($title);
        }

        return '<a href="' . e($link) . '" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline">' . e($title) . '</a>';
    }

    private function splitNationalities(?string $value): array
    {
        return collect(explode(',', (string)$value))
            ->map(fn($item) => trim($item))
            ->filter()
            ->map(fn($item) => mb_strtolower($item))
            ->unique()
            ->values()
            ->all();
    }

    private function formatNationalities(array $values): ?string
    {
        $nationalities = collect($values)
            ->flatMap(fn($value) => $this->splitNationalities((string)$value))
            ->unique()
            ->map(fn($value) => mb_convert_case($value, MB_CASE_TITLE, 'UTF-8'))
            ->values();

        return $nationalities->isEmpty() ? null : $nationalities->implode(', ');
    }

    private function formatReligionIdsHtml(array $ids): string
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if (empty($ids)) {
            return '—';
        }

        $labels = $this->religionLabels($ids);

        if (empty($labels)) {
            return '—';
        }

        return $this->formatListHtml(array_values($labels));
    }

    private function formatPlainValue($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—';
        }

        return (string)$value;
    }

    private function decodeJsonValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function religionLabels(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return DB::table('religion_translations')
            ->whereIn('religion_id', $ids)
            ->where('locale', app()->getLocale())
            ->pluck('path_text', 'religion_id')
            ->mapWithKeys(fn($label, $id) => [(int)$id => $label])
            ->toArray();
    }

    private function valueKey($value): string
    {
        if (is_array($value)) {
            ksort($value);
        }

        return md5(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function jsonTranslation(string $json): string
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            return $json;
        }

        return (string)($data[app()->getLocale()] ?? $data['cs'] ?? $data['en'] ?? reset($data));
    }

    private function concatenateUnique(array $values, string $separator): ?string
    {
        $values = collect($values)
            ->map(fn($value) => trim((string)$value))
            ->filter()
            ->unique()
            ->values();

        return $values->isEmpty() ? null : $values->implode($separator);
    }

    private function concatenateAlternativeNames(Collection $records): ?string
    {
        $values = $records
            ->map(fn(GlobalIdentity $record) => trim((string)$record->getRawOriginal('alternative_names')))
            ->filter()
            ->values();

        return $values->isEmpty() ? null : $values->implode("\n\n===\n\n");
    }

    private function concatenateAdminNotes(array $values): ?string
    {
        $values = collect($values)
            ->flatMap(fn($value) => explode(',', (string)$value))
            ->map(fn($value) => trim((string)$value))
            ->filter()
            ->unique()
            ->values();

        return $values->isEmpty() ? null : $values->implode(', ');
    }

    private function normalizeIds(array $ids): array
    {
        $ids = collect($ids)
            ->map(fn($id) => (int)$id)
            ->filter(fn(int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        sort($ids);

        return $ids;
    }

    private function logAudit(string $status, array $payload, array $result = [], ?string $errorMessage = null): void
    {
        try {
            $user = auth()->user();

            MergeAuditLog::query()->create([
                'tenant_id' => tenancy()->tenant?->id,
                'tenant_prefix' => tenancy()->tenant?->table_prefix,
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'entity' => 'global_identity',
                'operation' => 'strict_global_merge',
                'status' => $status,
                'payload' => $payload,
                'result' => $result,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            Log::error('[GlobalIdentityStrictMerge] failed to persist audit log: ' . $e->getMessage());
        }
    }
}
