<?php

namespace App\Http\Resources;

use App\Models\Letter;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LetterCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'collection' => config('app.name'),
        ];
    }

    public function with($request)
    {
        return [
            'meta' => [
                'total_unfiltered' => Letter::where('status', 'publish')->count(),
            ],
        ];
    }
}
