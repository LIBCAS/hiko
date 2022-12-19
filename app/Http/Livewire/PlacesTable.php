<?php

namespace App\Http\Livewire;

use App\Models\Place;
use Livewire\Component;
use Livewire\WithPagination;

class PlacesTable extends Component
{
    use WithPagination;

    public array $filters = [
        'order' => 'name',
    ];

    public function search()
    {
        $this->resetPage();
        session()->put('placesTableFilters', $this->filters);
    }

    public function resetFilters()
    {
        $this->reset();
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

    protected function findPlaces()
    {
        return Place::select('id', 'name', 'division', 'latitude', 'longitude', 'country')
            ->search($this->filters)
            ->orderBy($this->filters['order'])
            ->paginate(10);
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
            })
                ->toArray(),
        ];
    }
}
