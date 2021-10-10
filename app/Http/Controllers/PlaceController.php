<?php

namespace App\Http\Controllers;

use App\Models\Place;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    public function index()
    {
        return view('pages.places.index', [
            'title' => __('Místa'),
        ]);
    }

    public function create()
    {
    }

    public function store(Request $request)
    {
    }

    public function edit(Place $place)
    {
    }

    public function update(Request $request, Place $place)
    {
    }

    public function destroy(Place $place)
    {
    }
}
