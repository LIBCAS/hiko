<?php

namespace App\Http\Livewire;

use App\Models\Identity;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;

class IdentitiesTable extends LivewireDatatable
{
    public $model = Identity::class;

    public $labels;

    public function columns()
    {
        $labels = collect($this->labels)->map(function ($item, $key) {
            return ['id' => $key, 'name' => $item];
        })->toArray();

        return [
            Column::callback(['name', 'id', 'birth_year', 'death_year'], function ($name, $id, $birth_year, $death_year) {
                $dates = $this->formatDate($birth_year, $death_year);
                return view('tables.edit-link', ['route' => route('identities.edit', $id), 'label' => "$name $dates"]);
            })
                ->defaultSort('asc')
                ->label(__('Jméno'))
                ->filterable('name'),

            Column::callback(['type'], function ($type) {
                return $this->labels[$type];
            })
                ->label(__('Typ'))
                ->filterable(array_values($labels)),

            Column::callback(['alternative_names->variants'], function ($variants) {
                if (empty($variants) || $variants === 'null') {
                    return '';
                }

                $list = '<li>' . implode('</li><li>', json_decode($variants, true)) . '</li>';

                return "<ul>$list</ul>";
            })
                ->filterable()
                ->filterOn("JSON_EXTRACT(alternative_names, '$.variants')")
                ->label(__('Další jména')),

            Column::name('professions.name')
                ->label(__('Profese'))
                ->filterable(),

            Column::name('profession_categories.name')
                ->label(__('Kategorie'))
                ->filterable(),
        ];
    }

    protected function formatDate($birth, $death)
    {
        if (empty($birth) && empty($death)) {
            return '';
        }

        if ($birth && $death) {
            return "({$birth}–{$death})";
        }

        if ($birth) {
            return "({$birth}–)";
        }

        if ($death) {
            return "(–{$death})";
        }
    }
}
