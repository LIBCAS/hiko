<?php

namespace App\Http\Resources;

use App\Models\GlobalLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $scope = $this->resource instanceof GlobalLocation ? 'global' : 'local';
        $id = (int) $this->id;

        return [
            'id' => $id,
            'scope' => $scope,
            'reference' => "{$scope}-{$id}",
            'name' => $this->name,
            'type' => $this->type,
        ];
    }
}
