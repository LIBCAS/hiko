<?php

namespace App\Http\Livewire;

use App\Models\User;
use App\Models\Letter;
use Illuminate\Support\Facades\Auth;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\BooleanColumn;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;
use Mediconesystems\LivewireDatatables\NumberColumn;

class LettersTable extends LivewireDatatable
{
    public $hideable = 'select';

    public function builder()
    {
        return Letter::query()
            ->leftJoin('letter_user', function ($join) {
                $join->on('letter_user.letter_id', 'letters.id')->where('letter_user.user_id', '=', Auth::user()->id);
            })
            ->leftJoin('media', 'media.model_id', 'letters.id')->groupBy('letters.id');
    }

    public function columns()
    {
        // TODO: filtrování podle obrázků
        $currentUser = Auth::user();

        $columns = [
            Column::callback(['id', 'history'], function ($id, $history) {
                return view('tables.letter-actions', ['id' => $id, 'history' => $history]);
            }, 'actions')
                ->label(__('Akce')),

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
                return "{$day}/{$month}/{$year}";
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

            Column::name('destinations.name')
                ->label(__('Určení'))
                ->filterable(),

            Column::name('keywords.name')
                ->label(__('Klíčová slova'))
                ->hide()
                ->filterable(),

            Column::callback('media.id', function ($ids) {
                return $ids;
            })
                ->label(__('Přílohy'))
                ->filterOn('media.model_id')
                ->filterable(),

            Column::name('status')
                ->label(__('Status'))
                ->filterable(),
        ];

        if ($currentUser->can('manage-users')) {
            $editors = User::select(['name'])->get()->map(function ($user) {
                return [
                    'id' => $user->name,
                    'name' => $user->name,
                ];
            })->toArray();

            $columns[] = Column::name('users.name')
                ->label(__('Editoři'))
                ->filterable($editors);
        } else if ($currentUser->can('manage-metadata')) {
            $columns[] = BooleanColumn::name('letter_user.id')
                ->label(__('Moje záznamy'))
                ->filterable();
        }

        return $columns;
    }
}
