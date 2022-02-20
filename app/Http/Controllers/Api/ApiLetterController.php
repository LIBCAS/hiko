<?php

namespace App\Http\Controllers\Api;

use App\Models\Letter;
use App\Http\Controllers\Controller;
use App\Http\Resources\LetterResource;

class ApiLetterController extends Controller
{
    public function index()
    {
        return [];
    }

    public function show($uuid)
    {
        $letter = Letter::where('uuid', $uuid)
            ->where('status', 'publish')
            ->first();

        if (!$letter) {
            abort(404);
        }

        $letter->load([
            'identities' => function ($query) {
                return $query->select(['name']);
            },
            'places' => function ($query) {
                return $query->select(['name']);
            },
            'keywords' => function ($query) {
                return $query->select(['name']);
            },
        ]);

        return new LetterResource($letter);
    }
}
