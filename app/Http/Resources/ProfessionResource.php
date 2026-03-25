<?php

namespace App\Http\Resources;

use App\Models\GlobalProfession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $name = json_decode($this->getAttributes()['name'] ?? '{}', true);
        $scope = $this->resource instanceof GlobalProfession ? 'global' : 'local';
        $id = (int) $this->id;

        return [
            'id' => $id,
            'scope' => $scope,
            'reference' => "{$scope}-{$id}",
            'name' => [
                'cs' => $name['cs'] ?? '',
                'en' => $name['en'] ?? '',
            ],
            'category_id' => $this->profession_category_id,
        ];
    }
}
