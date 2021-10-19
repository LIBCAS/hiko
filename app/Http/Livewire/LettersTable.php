<?php

namespace App\Http\Livewire;

use App\Models\Letter;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\NumberColumn;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;

class LettersTable extends LivewireDatatable
{
    public $model = Letter::class;
    public $hideable = 'select';

    public function columns()
    {
        // TODO: filtrování podle obrázků a editorů

        return [

            Column::callback(['id'], function ($id) {
                return view('tables.letter-actions', ['id' => $id]);
            }, 'actions'),

            Column::name('id')
            ->label(__('ID'))
            ->defaultSort('asc')
            ->filterable(),

            Column::callback('copies', function ($copies) {
                return collect(json_decode($copies))->map(function ($copy) {
                    return $copy->signature;
                });
            })
                ->label(__('Signatura'))
                ->filterable()
                ->filterOn("copies->'$[*].signature'"),

            Column::callback(['date_year', 'date_month', 'date_day'], function ($year, $month, $day) {
                $year = $year ? $year : '?';
                $month = $month ? $month : '?';
                $day = $day ? $day : '?';
                return "$day/$month/$year";
            })
                ->label(__('Datum'))
                ->filterable()
                ->filterOn('date_computed'),

            Column::name('authors.name')
                ->label(__('Autor'))
                ->filterable(),

            Column::name('recipients.name')
                ->label(__('Příjemce'))
                ->filterable(),

            Column::name('origins.name')
                ->label(__('Odeslání'))
                ->filterable(),

            Column::name('keywords.name')
                ->label(__('Klíčová slova'))
                ->hide()
                ->filterable(),
        ];
    }
}
