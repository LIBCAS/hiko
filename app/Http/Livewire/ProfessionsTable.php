<?php

namespace App\Http\Livewire;

use App\Models\Profession;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;

class ProfessionsTable extends LivewireDatatable
{
    public $model = Profession::class;

    public function columns()
    {
        return [
            Column::name('name->en')
                ->defaultSort('asc')
                ->filterable()
                ->filterOn("JSON_EXTRACT(name, '$.en')")
                ->label('en'),
            Column::name('name->cs')
                ->filterable()
                ->filterOn("JSON_EXTRACT(name, '$.cs')")
                ->label('cs'),
        ];
    }
}
