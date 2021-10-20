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
        return [
            Column::callback(['name', 'id'], function ($name, $id) {
                return view('tables.edit-link', ['route' => route('places.edit', $id), 'label' => $name]);
            })
                ->defaultSort('asc')
                ->label(__('Jméno'))
                ->filterable('name'),

            Column::name('country')
                ->label(__('Země'))
                ->filterable(),

            Column::callback(['longitude', 'latitude'], function ($longitude, $latitude) {
                if (empty($longitude) || empty($latitude)) {
                    return '';
                }

                $url = "https://www.openstreetmap.org/?mlat=$latitude&mlon=$longitude&zoom=12";
                return "<a href='$url' target='_blank' class=''>$latitude,$longitude &#10697;</a>";
            })
                ->label(__('Souřadnice')),
        ];
    }
}
