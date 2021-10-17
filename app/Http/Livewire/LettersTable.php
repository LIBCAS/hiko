<?php

namespace App\Http\Livewire;

use App\Models\Letter;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;

class LettersTable extends LivewireDatatable
{
    public $model = Letter::class;

    public function columns()
    {
        return [
            Column::callback(['id'], function ($id) {
                return "<a href='" . route('letters.edit', $id) . "' class='font-semibold text-primary'>$id</a>";
            })
                ->defaultSort('asc')
                ->label(__('ID'))
                ->filterable('id'),
        ];
    }
}
