<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
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
