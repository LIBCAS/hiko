<?php

namespace App\Http\Livewire;

use App\Models\Location;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;

class LocationsTable extends LivewireDatatable
{
    public $model = Location::class;
    public $labels;

    public function columns()
    {
        $labels = collect($this->labels)->map(function ($item, $key) {
            return ['id' => $key, 'name' => $item];
        })->toArray();

        return [
            Column::callback(['name', 'id'], function ($name, $id) {
                return view('tables.edit-link', ['route' => route('locations.edit', $id), 'label' => $name]);
            })
                ->defaultSort('asc')
                ->label(__('Jméno'))
                ->filterable('name'),

            Column::callback(['type'], function ($type) {
                return $this->labels[$type];
            })
                ->label(__('Typ'))
                ->filterable(array_values($labels)),
        ];
    }
}
