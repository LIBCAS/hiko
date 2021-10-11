<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Country;
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
        return view('pages.places.form', [
            'title' => __('Nové místo'),
            'place' => new Place(),
            'action' => route('places.store'),
            'label' => __('Vytvořit'),
            'countries' => Country::all(),
        ]);
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
