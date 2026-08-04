<?php

namespace App\Services;

use App\Models\MetadataDigestDelivery;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MetadataDigestCollector
{
    public function collect(MetadataDigestDelivery $delivery): array
    {
        $delivery->loadMissing('run');
        $tenants = Tenant::query()
            ->with('domains')
            ->whereIn('id', $delivery->tenant_ids)
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            throw new \RuntimeException('The digest recipient has no active tenant membership.');
        }

        $sections = $this->emptySections();
        $primaryBaseUrl = $this->baseUrl($tenants->first());

        foreach (array_keys($sections) as $key) {
            $sections[$key]['groups'][] = $this->group(
                $key,
                'global',
                null,
                'Global',
                null,
                $primaryBaseUrl,
                $delivery->run->period_start,
                $delivery->run->period_end
            );
        }

        foreach ($tenants as $tenant) {
            $baseUrl = $this->baseUrl($tenant);

            foreach (array_keys($sections) as $key) {
                $sections[$key]['groups'][] = $this->group(
                    $key,
                    'local',
                    $tenant->table_prefix,
                    $tenant->table_prefix,
                    $tenant->table_prefix,
                    $baseUrl,
                    $delivery->run->period_start,
                    $delivery->run->period_end
                );
            }
        }

        foreach ($sections as &$section) {
            $section['groups'] = array_values(array_filter(
                $section['groups'],
                fn(array $group) => count($group['records']) > 0
            ));
            $section['total'] = array_sum(array_map(
                fn(array $group) => count($group['records']),
                $section['groups']
            ));
        }
        unset($section);

        return [
            'period_start' => $delivery->run->period_start,
            'period_end' => $delivery->run->period_end,
            'sections' => $sections,
            'total' => array_sum(array_column($sections, 'total')),
        ];
    }

    private function definitions(): array
    {
        return [
            'keyword_categories' => [
                'suffix' => 'keyword_categories',
                'local_route' => 'keywords.category.edit',
                'global_route' => 'global.keywords.category.edit',
            ],
            'keywords' => [
                'suffix' => 'keywords',
                'category_suffix' => 'keyword_categories',
                'category_foreign_key' => 'keyword_category_id',
                'local_route' => 'keywords.edit',
                'global_route' => 'global.keywords.edit',
            ],
            'profession_categories' => [
                'suffix' => 'profession_categories',
                'local_route' => 'professions.category.edit',
                'global_route' => 'global.professions.category.edit',
            ],
            'professions' => [
                'suffix' => 'professions',
                'category_suffix' => 'profession_categories',
                'category_foreign_key' => 'profession_category_id',
                'local_route' => 'professions.edit',
                'global_route' => 'global.professions.edit',
            ],
            'places' => [
                'suffix' => 'places',
                'local_route' => 'places.edit',
                'global_route' => 'global.places.edit',
            ],
            'locations' => [
                'suffix' => 'locations',
                'local_route' => 'locations.edit',
                'global_route' => 'global.locations.edit',
            ],
            'identities' => [
                'suffix' => 'identities',
                'local_route' => 'identities.edit',
                'global_route' => 'global.identities.edit',
            ],
        ];
    }

    private function emptySections(): array
    {
        return [
            'keywords' => $this->section(__('hiko.keywords'), ['id', 'cs', 'en', 'category']),
            'keyword_categories' => $this->section(__('hiko.keyword_categories'), ['id', 'cs', 'en']),
            'professions' => $this->section(__('hiko.professions'), ['id', 'cs', 'en', 'category']),
            'profession_categories' => $this->section(__('hiko.professions_category'), ['id', 'cs', 'en']),
            'places' => $this->section(__('hiko.places'), [
                'id', 'name', 'division', 'country', 'note', 'latitude', 'longitude', 'geoname_id',
            ]),
            'locations' => $this->section(__('hiko.locations'), ['id', 'name', 'type']),
            'identities' => $this->section(__('hiko.identities'), [
                'id', 'name', 'type', 'surname', 'forename', 'related_names', 'nationality', 'gender',
                'birth_year', 'death_year', 'viaf_id', 'note', 'admin_notes',
            ]),
        ];
    }

    private function section(string $label, array $headings): array
    {
        return ['label' => $label, 'headings' => $headings, 'groups' => [], 'total' => 0];
    }

    private function group(
        string $key,
        string $scope,
        ?string $tenant,
        string $sheet,
        ?string $prefix,
        string $baseUrl,
        CarbonInterface $start,
        CarbonInterface $end
    ): array {
        $definition = $this->definitions()[$key];
        $route = $scope === 'global' ? $definition['global_route'] : $definition['local_route'];
        $records = $this->records($key, $scope, $prefix, $start, $end)
            ->map(fn(object $record) => [
                'id' => $record->id,
                'href' => $baseUrl . route($route, $record->id, false),
                'export' => $this->mapRecord($key, $record, $scope),
            ])
            ->all();

        return [
            'scope' => $scope,
            'tenant' => $tenant,
            'sheet' => Str::limit($sheet, 31, ''),
            'records' => $records,
        ];
    }

    private function records(
        string $key,
        string $scope,
        ?string $prefix,
        CarbonInterface $start,
        CarbonInterface $end
    ): Collection {
        $definition = $this->definitions()[$key];
        $table = $this->table($definition['suffix'], $scope, $prefix);
        $query = DB::connection('mysql')->table("{$table} as records")->select('records.*');

        if (isset($definition['category_suffix'])) {
            $categoryTable = $this->table($definition['category_suffix'], $scope, $prefix);
            $query->leftJoin(
                "{$categoryTable} as categories",
                'categories.id',
                '=',
                'records.' . $definition['category_foreign_key']
            )->addSelect('categories.name as digest_category_name');
        }

        return $query
            ->where('records.created_at', '>=', $start)
            ->where('records.created_at', '<', $end)
            ->orderBy('records.id')
            ->get();
    }

    private function table(string $suffix, string $scope, ?string $prefix): string
    {
        return $scope === 'global' ? "global_{$suffix}" : "{$prefix}__{$suffix}";
    }

    private function mapRecord(string $key, object $record, string $scope): array
    {
        return match ($key) {
            'keyword_categories', 'profession_categories' => $this->mapCategory($record),
            'keywords', 'professions' => $this->mapNamedMetadata($record),
            'places' => $this->mapPlace($record),
            'locations' => [$record->id, $record->name, $record->type],
            'identities' => $this->mapIdentity($record, $scope),
        };
    }

    private function mapCategory(object $record): array
    {
        $name = $this->translations($record->name);

        return [$record->id, $name['cs'] ?? '', $name['en'] ?? ''];
    }

    private function mapNamedMetadata(object $record): array
    {
        $name = $this->translations($record->name);
        $category = $this->translations($record->digest_category_name ?? null);

        return [$record->id, $name['cs'] ?? '', $name['en'] ?? '', implode(' | ', array_values($category))];
    }

    private function mapPlace(object $record): array
    {
        return [
            $record->id,
            $record->name,
            $record->division ?? null,
            $record->country ?? null,
            $record->note ?? null,
            $record->latitude ?? null,
            $record->longitude ?? null,
            $record->geoname_id ?? null,
        ];
    }

    private function mapIdentity(object $record, string $scope): array
    {
        return [
            $record->id,
            $record->name,
            $record->type,
            $record->surname ?? null,
            $record->forename ?? null,
            $this->formatRelatedNames($record->related_names ?? null),
            $record->nationality ?? null,
            $record->gender ?? null,
            $record->birth_year ?? null,
            $record->death_year ?? null,
            $record->viaf_id ?? null,
            $record->note ?? null,
            $scope === 'global' ? ($record->admin_notes ?? null) : null,
        ];
    }

    private function translations(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function formatRelatedNames(mixed $relatedNames): string
    {
        if (is_string($relatedNames)) {
            $relatedNames = json_decode($relatedNames, true);
        }

        if (!is_array($relatedNames)) {
            return '';
        }

        return collect($relatedNames)->map(function ($name) {
            if (!is_array($name)) {
                return (string) $name;
            }

            return trim(implode(' ', array_filter([
                $name['surname'] ?? null,
                $name['forename'] ?? null,
                $name['general_name_modifier'] ?? null,
            ])));
        })->filter()->implode(' | ');
    }

    private function baseUrl(Tenant $tenant): string
    {
        if ($tenant->public_url) {
            return rtrim($tenant->public_url, '/');
        }

        $domain = $tenant->domains->first()?->domain;

        if (!$domain) {
            throw new \RuntimeException("Tenant {$tenant->table_prefix} has no domain for digest links.");
        }

        $scheme = str_contains($domain, 'localhost') ? 'http' : config('metadata_digest.url_scheme', 'https');

        return $scheme . '://' . $domain;
    }
}
