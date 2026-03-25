<?php

namespace App\Http\Resources;

use App\Models\GlobalKeyword;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KeywordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Handle translatable name manually to ensure JSON structure
        $name = json_decode($this->getAttributes()['name'] ?? '{}', true);
        $scope = $this->resource instanceof GlobalKeyword ? 'global' : 'local';
        $id = (int) $this->id;

        return [
            'id' => $id,
            'scope' => $scope,
            'reference' => "{$scope}-{$id}",
            'name' => [
                'cs' => $name['cs'] ?? '',
                'en' => $name['en'] ?? '',
            ],
            'category_id' => $this->keyword_category_id,
        ];
    }
}
