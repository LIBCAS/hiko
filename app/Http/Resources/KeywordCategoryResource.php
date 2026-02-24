<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KeywordCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $name = json_decode($this->getAttributes()['name'] ?? '{}', true);

        return [
            'id' => $this->id,
            'name' => [
                'cs' => $name['cs'] ?? '',
                'en' => $name['en'] ?? '',
            ],
        ];
    }
}
