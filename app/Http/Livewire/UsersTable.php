<?php

namespace App\Http\Livewire;

use App\Models\User;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;

class UsersTable extends LivewireDatatable
{
    public $model = User::class;

    public function builder()
    {
        return User::query();
    }

    public function columns()
    {
        return [
            Column::callback(['name', 'id'], function ($name, $id) {
                return "<a href='" . route('users.edit', ['user' => $id]) . "' class='font-semibold text-primary'>$name</a>";
            })
                ->defaultSort('asc')
                ->label(__('Jméno'))
                ->filterable('name'),

            Column::name('role')
                ->filterable(),

            Column::callback(['deactivated_at'], function ($deactivated_at) {
                return empty($deactivated_at) ? __('Aktivní') : __('Neaktivní');
            })
                ->label('Status'),
        ];
    }
}
