<?php

namespace App\Http\Livewire;

use App\Models\User;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;

class UsersTable extends LivewireDatatable
{
    public $model = User::class;
    public $roles;

    public function builder()
    {
        return User::query();
    }

    public function columns()
    {
        $roles = collect($this->roles)->map(function ($item, $key) {
            return ['id' => $key, 'name' => $item];
        })->toArray();

        return [
            Column::callback(['name', 'id'], function ($name, $id) {
                return view('tables.edit-link', ['route' => route('users.edit', $id), 'label' => $name]);
            })
                ->defaultSort('asc')
                ->label(__('Jméno'))
                ->filterable('name'),

            Column::callback(['role'], function ($role) {
                return $this->roles[$role];
            })
                ->label(__('Role'))
                ->filterable(array_values($roles)),

            Column::callback(['deactivated_at'], function ($deactivated_at) {
                return empty($deactivated_at) ? __('Aktivní') : __('Neaktivní');
            })
                ->label('Status'),
        ];
    }
}
