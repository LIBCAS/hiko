<?php

namespace App\Livewire;

use App\Services\Geonames;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

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

        Log::info("City selected: ID={$id}, Latitude={$latitude}, Longitude={$longitude}");
    }

    public function updatedSearch()
    {
        Log::info("Search updated: '{$this->search}'");

        if (strlen($this->search) >= 2) {
            try {
                $this->searchResults = app(Geonames::class)->search($this->search)->toArray();
                $this->error = '';
                Log::info("Search results fetched: " . json_encode($this->searchResults));
            } catch (\Exception $e) {
                $this->searchResults = [];
                $this->error = $e->getMessage();
                Log::error("Error fetching search results: " . $e->getMessage());
            }
        } else {
            $this->searchResults = [];
            $this->error = '';
            Log::info("Search term too short. Clearing results.");
        }
    }

    public function render()
    {
        return view('livewire.geonames-search');
    }
}
