<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReligionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $name = $this->translated_name ?? $this->name;
        $pathText = $this->translated_path_text ?? $this->path_text ?? $name;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $name,
            'path_text' => $pathText,
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
