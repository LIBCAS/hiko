<?php

namespace App\Livewire;

use App\Services\Geonames;
use Livewire\Component;

class GeonamesSearch extends Component
{
    public $search = '';
    public $searchResults = [];
    public $error = '';
    public $selectedCityName = '';
    public $latitude = '';
    public $longitude = '';
    public $geoname_id = '';

    protected $listeners = ['updateCoordinates' => 'updateCoordinatesFromJS'];

    public function updateCoordinatesFromJS($data)
    {
        $this->geoname_id = $data['id'];
        $this->selectedCityName = $data['name'];
        $this->latitude = $data['latitude'];
        $this->longitude = $data['longitude'];
        $this->dispatch('updateMainForm', [
            'id' => $this->geoname_id,
            'name' => $this->selectedCityName,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ]);
    }

    public function selectCity($id, $latitude, $longitude, $name)
    {
       $this->search = '';
        $this->dispatch('citySelected', [
            'id' => $id,
            'name' => $name,
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

    public function mount($latitude = null, $longitude = null, $geoname_id = null)
    {
      $this->latitude = $latitude;
      $this->longitude = $longitude;
      $this->geoname_id = $geoname_id;
    }


    public function render()
    {
        return view('livewire.geonames-search');
    }
}
