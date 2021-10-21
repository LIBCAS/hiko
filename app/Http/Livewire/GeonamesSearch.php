<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Symfony\Component\HttpFoundation\Request;

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
            $this->searchResults = json_decode(
                file_get_contents(route('ajax.geonames') . '?search=' . urlencode($this->search))
            );
        }

        return view('livewire.geonames-search');
    }
}
