<?php

namespace App\Http\Resources;

use App\Models\GlobalPlace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $scope = $this->resource instanceof GlobalPlace ? 'global' : 'local';
        $id = (int) $this->id;

        return [
            'id' => $id,
            'scope' => $scope,
            'reference' => "{$scope}-{$id}",
            'name' => $this->name,
            'country' => $this->country,
            'division' => $this->division,
            'note' => $this->note,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'geoname_id' => $this->geoname_id,
            'alternative_names' => $this->alternative_names,
        ];
    }
}
