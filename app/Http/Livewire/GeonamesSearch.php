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

    public function updatedSearch()
    {
        if (strlen($this->search) >= 2) {
            try {
                $this->searchResults = app(Geonames::class)->search($this->search)->toArray();
                $this->error = '';
            } catch (\Exception $e) {
                $this->searchResults = [];
                $this->error = $e->getMessage();
            }
        }
    }

    public function render()
    {
        return view('livewire.geonames-search');
    }
}
