<?php

namespace App\Http\Livewire;

use App\Models\KeywordCategory;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;

class KeywordsCategoriesTable extends LivewireDatatable
{
    public $model = KeywordCategory::class;

    public function columns()
    {
        return [
            Column::callback(['id'], function ($id) {
                return "<a href='" . route('keywords.category.edit', $id) . "' class='font-semibold text-primary'>" . __('Upravit') . "</a>";
            }),

            Column::callback(['name->en'], function ($name) {
                if (empty($name) || $name === 'null') {
                    return '';
                }
                return $name;
            })
                ->defaultSort('asc')
                ->filterable()
                ->filterOn("JSON_EXTRACT(name, '$.en')")
                ->label('en'),

            Column::callback(['name->cs'], function ($name) {
                if (empty($name) || $name === 'null') {
                    return '';
                }
                return $name;
            })
                ->filterable()
                ->filterOn("JSON_EXTRACT(name, '$.cs')")
                ->label('cs'),
        ];
    }
}
