<?php

namespace App\Http\Resources;

use App\Models\Letter;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LetterCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
            'collection' => config('app.name'),
        ];
    }

    public function with($request): array
    {
        return [
            'meta' => [
                'total_unfiltered' => Letter::where('status', 'publish')->count(),
            ],
        ];
    }
}
