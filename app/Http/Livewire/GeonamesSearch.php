<?php

namespace App\Http\Livewire;

use App\Services\Geonames;
use Livewire\Component;

class GeonamesSearch extends Component
{
    public $search = '';
    public $searchResults = [];
    public $error = '';

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
            try {
                $this->searchResults = (new Geonames)->search($this->search);
                $this->error = '';
            } catch (\Throwable $th) {
                $this->searchResults = [];
                $this->error = $th->getMessage();
            }
        }

        return view('livewire.geonames-search');
    }
}
