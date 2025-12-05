<?php

namespace App\Livewire;

use App\Models\Place;
use App\Models\GlobalPlace;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlacesTable extends Component
{
    use WithPagination;

    public array $filters = [
        'order' => 'name',
        'name' => '',
        'country' => '',
        'note' => '',
        'source' => 'all', // all, local, global
        'has_geoname' => 'all', // all, yes, no
    ];

    public function search()
    {
        $this->resetPage();
        session()->put('placesTableFilters', $this->filters);
    }

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
    }

    public function mount()
    {
        if (session()->has('placesTableFilters')) {
            $this->filters = session()->get('placesTableFilters');
        }
    }

    public function updatedFilters()
    {
        $this->resetPage('placesPage');
        session()->put('placesTableFilters', $this->filters);
    }

    public function render()
    {
        $places = $this->findPlaces();

        return view('livewire.places-table', [
            'tableData' => $this->formatTableData($places),
            'pagination' => $places,
        ]);
    }

    protected function findPlaces(): LengthAwarePaginator
    {
        $filters = $this->filters;
        $source = $filters['source'] ?? 'all';

        if ($source === 'local') {
            return $this->getLocalPlacesQuery()->orderBy($filters['order'])->paginate(25, ['*'], 'placesPage');
        } elseif ($source === 'global') {
            return $this->getGlobalPlacesQuery()->orderBy($filters['order'])->paginate(25, ['*'], 'placesPage');
        } else {
            // Merge both local and global
            $localQuery = $this->getLocalPlacesQuery();
            $globalQuery = $this->getGlobalPlacesQuery();

            if (!$localQuery) {
                return $globalQuery->orderBy($filters['order'])->paginate(25, ['*'], 'placesPage');
            }

            return $this->mergeQueries($localQuery, $globalQuery);
        }
    }

    protected function getLocalPlacesQuery()
    {
        if (!tenancy()->initialized) {
            return null;
        }

        $prefix = tenancy()->tenant->table_prefix;
        $query = DB::table("{$prefix}__places")
            ->select(
                'id',
                'name',
                'additional_name',
                'division',
                'latitude',
                'longitude',
                'country',
                'note',
                'geoname_id',
                DB::raw("'local' as source")
            );

        $this->applyFilters($query, $this->filters);
        return $query;
    }

    protected function getGlobalPlacesQuery()
    {
        $query = DB::table('global_places')
            ->select(
                'id',
                'name',
                'additional_name',
                'division',
                'latitude',
                'longitude',
                'country',
                'note',
                'geoname_id',
                DB::raw("'global' as source")
            );

        $this->applyFilters($query, $this->filters);
        return $query;
    }

    protected function mergeQueries($localQuery, $globalQuery): LengthAwarePaginator
    {
        $filters = $this->filters;

        // Use Laravel's native unionAll
        $unionQuery = $localQuery->unionAll($globalQuery);

        // Order and paginate
        return $unionQuery->orderBy($filters['order'])->paginate(25, ['*'], 'placesPage');
    }

    protected function applyFilters($query, $filters): void
    {
        if (!empty($filters['name'])) {
            $searchTerm = $filters['name'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('division', 'like', "%{$searchTerm}%")
                  ->orWhere('country', 'like', "%{$searchTerm}%")
                  ->orWhere('additional_name', 'like', "%{$searchTerm}%")
                  ->orWhereRaw("JSON_SEARCH(alternative_names, 'one', ?) IS NOT NULL", ["%{$searchTerm}%"]);
            });
        }

        if (!empty($filters['country'])) {
            $query->where('country', 'like', "%{$filters['country']}%");
        }

        if (!empty($filters['note'])) {
            $query->where('note', 'like', "%{$filters['note']}%");
        }

        // Filter by has_geoname
        if (!empty($filters['has_geoname']) && $filters['has_geoname'] !== 'all') {
            if ($filters['has_geoname'] === 'yes') {
                $query->whereNotNull('geoname_id');
            } elseif ($filters['has_geoname'] === 'no') {
                $query->whereNull('geoname_id');
            }
        }
    }

    protected function formatTableData($data): array
    {
        return [
            'header' => [
                __('hiko.name'),
                __('hiko.additional_name'),
                __('hiko.division'),
                __('hiko.country'),
                __('hiko.coordinates'),
                __('hiko.source'),
                __('hiko.geoname_id')
            ],
            'rows' => $data->map(function ($place) {
                $hasLatLng = $place->latitude && $place->longitude;
                $isGlobal = $place->source === 'global';

                // Determine edit route
                $editRoute = $isGlobal
                    ? route('global.places.edit', $place->id)
                    : route('places.edit', $place->id);

                return [
                    [
                        'component' => [
                            'args' => [
                                'link' => $editRoute,
                                'label' => $place->name,
                            ],
                            'name' => 'tables.edit-link',
                        ],
                    ],
                    [
                        'label' => $place->additional_name ?? '',
                    ],
                    [
                        'label' => $place->division ?? '',
                    ],
                    [
                        'label' => $place->country ?? '',
                    ],
                    [
                        'label' => $hasLatLng ? "{$place->latitude},{$place->longitude}" : '',
                        'link' => $hasLatLng ? "https://www.openstreetmap.org/?mlat={$place->latitude}&mlon={$place->longitude}&zoom=12" : '',
                        'external' => $hasLatLng,
                    ],
                    [
                        'label' => $isGlobal ? ("<span class='inline-block bg-red-100 text-red-600 border border-red-200 text-xs uppercase px-2 py-1 rounded-full font-medium'>" . __('hiko.global') . "</span>") : ("<span class='inline-block text-blue-600 bg-blue-100 border border-blue-200 text-xs uppercase px-2 py-1 rounded-full font-medium'>" . __('hiko.local') . "</span>"),
                    ],
                    [
                        'label' => $place->geoname_id ?? '',
                    ],
                ];
            })->toArray(),
        ];
    }
}
