<?php

namespace App\Livewire;

use App\Models\Location;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as PaginationPaginator;

class LocationsTable extends Component
{
    use WithPagination;

    public array $filters = [
        'order' => 'name',
        'name' => '',
        'type' => '',
        'source' => 'all', // all, local, global
    ];

    public array $types;

    public function search()
    {
        $this->resetPage('locationsPage');
        session()->put('locationsTableFilters', $this->filters);
    }

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
    }

    public function mount()
    {
        $this->types = Location::types();

        if (session()->has('locationsTableFilters')) {
            $this->filters = session()->get('locationsTableFilters');
        }
    }

    public function updatedFilters()
    {
        $this->search();
    }

    public function render()
    {
        $locations = $this->findLocations();

        return view('livewire.locations-table', [
            'tableData' => $this->formatTableData($locations),
            'pagination' => $locations,
        ]);
    }

    protected function findLocations(): LengthAwarePaginator
    {
        $filters = $this->filters;
        $source = $filters['source'] ?? 'all';
        $prefix = tenancy()->initialized ? tenancy()->tenant->table_prefix : null;

        if (!$prefix) {
            return new PaginationPaginator([], 0, 25);
        }

        $manifestationsTable = "{$prefix}__manifestations";
        $locationsTable = "{$prefix}__locations";

        // Local Query
        $localQuery = null;
        if ($source === 'all' || $source === 'local') {
            $localCountSubquery = "(
                SELECT COUNT(DISTINCT letter_id) FROM `{$manifestationsTable}`
                WHERE `{$manifestationsTable}`.repository_id = `{$locationsTable}`.id
                OR `{$manifestationsTable}`.archive_id = `{$locationsTable}`.id
                OR `{$manifestationsTable}`.collection_id = `{$locationsTable}`.id
            )";
            $localQuery = DB::table($locationsTable)
                ->select(
                    'id',
                    'name',
                    'type',
                    DB::raw("'local' as source"),
                    DB::raw("$localCountSubquery as letters_count")
                );
            $this->applyFilters($localQuery, $filters);
        }

        // Global Query
        $globalQuery = null;
        if ($source === 'all' || $source === 'global') {
            $globalCountSubquery = "(
                SELECT COUNT(DISTINCT letter_id) FROM `{$manifestationsTable}`
                WHERE `{$manifestationsTable}`.global_repository_id = global_locations.id
                   OR `{$manifestationsTable}`.global_archive_id = global_locations.id
                   OR `{$manifestationsTable}`.global_collection_id = global_locations.id
            )";
            $globalQuery = DB::table('global_locations')
                ->select(
                    'id',
                    'name',
                    'type',
                    DB::raw("'global' as source"),
                    DB::raw("$globalCountSubquery as letters_count")
                );
            $this->applyFilters($globalQuery, $filters);
        }

        // Combine Queries
        if ($localQuery && $globalQuery) {
            $query = $localQuery->unionAll($globalQuery);
        } elseif ($localQuery) {
            $query = $localQuery;
        } else {
            $query = $globalQuery;
        }

        if (!$query) {
            return new PaginationPaginator([], 0, 25);
        }

        $direction = $filters['order'] === 'letters_count' ? 'desc' : 'asc';

        return $query->orderBy($filters['order'], $direction)
                     ->paginate(25, ['*'], 'locationsPage');
    }

    protected function applyFilters($query, $filters): void
    {
        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
    }

    protected function formatTableData($data): array
    {
        return [
            'header' => [
                __('hiko.name'),
                __('hiko.type'),
                __('hiko.source'),
                __('hiko.letters_count')
            ],
            'rows' => $data->map(function ($location) {
                $isGlobal = $location->source === 'global';

                // Determine edit route based on source
                $editRoute = $isGlobal
                    ? (auth()->user()->can('manage-users') ? route('global.locations.edit', $location->id) : '#')
                    : route('locations.edit', $location->id);

                // For global locations, disable link if user cannot manage them
                $nameCell = [
                    'label' => $location->name,
                ];

                if (!$isGlobal || auth()->user()->can('manage-users')) {
                    $nameCell = [
                        'component' => [
                            'args' => [
                                'link' => $editRoute,
                                'label' => $location->name,
                            ],
                            'name' => 'tables.edit-link',
                        ],
                    ];
                }

                return [
                    $nameCell,
                    [
                        'label' => __("hiko.{$location->type}"),
                    ],
                    [
                        'label' => $isGlobal
                            ? "<span class='inline-block bg-red-100 text-red-600 border border-red-200 text-xs uppercase px-2 py-1 rounded-full font-medium'>" . __('hiko.global') . "</span>"
                            : "<span class='inline-block text-blue-600 bg-blue-100 border border-blue-200 text-xs uppercase px-2 py-1 rounded-full font-medium'>" . __('hiko.local') . "</span>",
                        'isHtml' => true
                    ],
                    [
                        'label' => $location->letters_count,
                    ]
                ];
            })->toArray(),
        ];
    }
}
