<?php

namespace App\Http\Livewire;

use App\Models\Place;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;

class PlacesTable extends LivewireDatatable
{
    public $model = Place::class;

    public function columns()
    {
        //
    }
}
