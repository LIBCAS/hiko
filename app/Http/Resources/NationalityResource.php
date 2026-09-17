<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NationalityResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => (int) $this->id, 'name' => $this->getTranslations('name')];
    }
}
