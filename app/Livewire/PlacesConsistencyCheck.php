<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Place;
use App\Models\GlobalPlace;
use App\Http\Requests\PlaceRequest;
use App\Http\Requests\GlobalPlaceRequest;
use Illuminate\Support\Facades\Validator;

class PlacesConsistencyCheck extends Component
{
    public $scope = 'all'; // all, local, global
    public $isScanning = false;
    public $issues = [];

    public function scan()
    {
        $this->isScanning = true;
        $this->issues = [];

        // Scan Local Places
        if ($this->scope === 'local' || $this->scope === 'all') {
            if (tenancy()->initialized) {
                $rules = (new PlaceRequest)->rules();

                // We perform a chunked scan to avoid memory issues
                Place::chunk(200, function ($places) use ($rules) {
                    foreach ($places as $place) {
                        $validator = Validator::make($place->attributesToArray(), $rules);

                        if ($validator->fails()) {
                            foreach ($validator->errors()->all() as $error) {
                                $this->issues[] = [
                                    'type' => 'local',
                                    'id' => $place->id,
                                    'name' => $place->name,
                                    'error' => $error,
                                ];
                            }
                        }
                    }
                });
            }
        }

        // Scan Global Places
        if ($this->scope === 'global' || $this->scope === 'all') {
            $rules = (new GlobalPlaceRequest)->rules();

            GlobalPlace::chunk(200, function ($places) use ($rules) {
                foreach ($places as $place) {
                    $validator = Validator::make($place->attributesToArray(), $rules);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $error) {
                            $this->issues[] = [
                                'type' => 'global',
                                'id' => $place->id,
                                'name' => $place->name,
                                'error' => $error,
                            ];
                        }
                    }
                }
            });
        }

        $this->isScanning = false;
    }

    public function render()
    {
        return view('livewire.places-consistency-check');
    }
}
