<?php

namespace App\Http\Livewire;

use App\Models\Location;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;

class LocationsTable extends LivewireDatatable
{
    public $model = Location::class;

    public function builder()
    {



        return Location::query()
            ->selectRaw("REPLACE('type', 'archive', 'Archivy') as `type`");
    }

    public function columns()
    {
        return [
            Column::callback(['name', 'id'], function ($name, $id) {
                return "<a href='" . route('locations.edit', ['location' => $id]) . "' class='font-semibold text-primary'>$name</a>";
            })
                ->defaultSort('asc')
                ->label(__('Jméno'))
                ->filterable('name'),
        ];
    }
}
