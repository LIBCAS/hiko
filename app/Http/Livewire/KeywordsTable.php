<?php

namespace App\Http\Livewire;

use App\Models\Keyword;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;

class KeywordsTable extends LivewireDatatable
{
    public $model = Keyword::class;

    public function columns()
    {
        return [
            Column::callback(['id'], function ($id) {
                return view('tables.edit-link', ['route' => route('keywords.edit', $id), 'label' => __('Upravit')]);
            }),

            Column::callback(['name->en'], function ($name) {
                if (empty($name) || $name === 'null') {
                    return '';
                }
                return $name;
            }, 'en')
                ->defaultSort('asc')
                ->filterable()
                ->filterOn("JSON_EXTRACT(keywords.name, '$.en')")
                ->label('en'),

            Column::callback(['name->cs'], function ($name) {
                if (empty($name) || $name === 'null') {
                    return '';
                }
                return $name;
            }, 'cs')
                ->filterable()
                ->filterOn("JSON_EXTRACT(keywords.name, '$.cs')")
                ->label('cs'),

            Column::callback(['keyword_category.name'], function ($keyword_category) {
                if (empty($keyword_category)) {
                    return '';
                }
                return implode(' | ', array_values(json_decode($keyword_category, true)));
            }, 'category')
                ->filterable()
                ->label(__('Kategorie')),
        ];
    }
}
