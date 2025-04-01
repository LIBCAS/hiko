<?php

namespace App\Livewire;

use App\Models\Place;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlacesTable extends Component
{
    use WithPagination;

    public array $filters = [
        'order' => 'name',
        'name' => '',
        'country' => '',
        'note' => '',
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
    
        $query = Place::select('id', 'name', 'division', 'latitude', 'longitude', 'country');
    
        if (tenancy()->initialized) {
            $prefix = tenancy()->tenant->table_prefix;
            $query->from("{$prefix}__places");
        }
    
        if (!empty($filters['name'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['name']}%")
                  ->orWhere('division', 'like', "%{$filters['name']}%")
                  ->orWhere('country', 'like', "%{$filters['name']}%");
                for ($i = 0; $i < 50; $i++) {
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(alternative_names, '$[$i]')) LIKE ?", ["%{$filters['name']}%"]);
                }
            });
        }
    
        if (!empty($filters['country'])) {
            $query->where('country', 'like', "%{$filters['country']}%");
        }
    
        if (!empty($filters['note'])) {
            $query->where('note', 'like', "%{$filters['note']}%");
        }
    
        $query->orderBy($filters['order']);
    
        return $query->paginate(25, ['*'], 'placesPage');
    }      

    protected function formatTableData($data): array
    {
        return [
            'header' => [__('hiko.name'), __('hiko.division'), __('hiko.country'), __('hiko.coordinates')],
            'rows' => $data->map(function ($place) {
                $hasLatLng = $place->latitude && $place->longitude;
                return [
                    [
                        'component' => [
                            'args' => [
                                'link' => route('places.edit', $place->id),
                                'label' => $place->name,
                            ],
                            'name' => 'tables.edit-link',
                        ],
                    ],
                    [
                        'label' => $place->division,
                    ],
                    [
                        'label' => $place->country,
                    ],
                    [
                        'label' => $hasLatLng ? "{$place->latitude},{$place->longitude}" : '',
                        'link' => $hasLatLng ? "https://www.openstreetmap.org/?mlat={$place->latitude}&mlon={$place->longitude}&zoom=12" : '',
                        'external' => $hasLatLng,
                    ],
                ];
            })->toArray(),
        ];
    }
} 
