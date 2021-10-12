<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Request;

class GeonamesSearch extends Component
{
    public $search = '';
    public $searchResults = [];

    public function selectCity($id, $latitude, $longitude)
    {
        $this->search = '';

        $this->emit('citySelected', [
            'id' => $id,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    public function render()
    {
        if (strlen($this->search) >= 2) {
            $response = app()->handle(
                Request::create(route('ajax.geonames') . '?search=' . $this->search, 'GET')
            );

            $this->searchResults = json_decode($response->getContent());
        }

        return view('livewire.geonames-search');
    }
}
