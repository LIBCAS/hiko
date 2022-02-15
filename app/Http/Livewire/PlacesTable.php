<?php

namespace App\Http\Livewire;

use App\Models\Place;
use Livewire\Component;
use Livewire\WithPagination;

class PlacesTable extends Component
{
    use WithPagination;

    public $filters = [
        'order' => 'name',
    ];

    public function search()
    {
        $this->resetPage();
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
        $query = Place::select('id', 'name', 'latitude', 'longitude', 'country');

        if (isset($this->filters['name']) && !empty($this->filters['name'])) {
            $query->where('name', 'LIKE', "%" . $this->filters['name'] . "%");
        }

        if (isset($this->filters['country']) && !empty($this->filters['country'])) {
            $query->where('country', 'LIKE', "%" . $this->filters['country'] . "%");
        }

        $query->orderBy($this->filters['order']);

        return $query->paginate(10);
    }

    protected function formatTableData($data)
    {
        return [
            'header' => [__('hiko.name'), __('hiko.country'), __('hiko.coordinates')],
            'rows' => $data->map(function ($place) {

                return [
                    [
                        'label' => $place->name,
                        'link' => route('places.edit', $place->id),
                    ],
                    [
                        'label' => $place->country,
                    ],
                    [
                        'label' => "{$place->latitude},{$place->longitude}",
                        'link' => "https://www.openstreetmap.org/?mlat={$place->latitude}&mlon={$place->longitude}&zoom=12",
                        'external' => true,
                    ],
                ];
            })->toArray(),
        ];
    }
}
