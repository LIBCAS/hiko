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
        // TODO: syntax error in mysql
        return [
            Column::name('name->en')
                ->filterable()
                ->label('en'),
            Column::name('name->cs')
                ->filterable()
                ->label('cs'),
        ];
    }
}
