<?php

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UsersTable extends Component
{
    use WithPagination;

    public $filters = [
        'order' => 'name',
    ];

    public $roles;

    public function search()
    {
        $this->resetPage();
    }

    public function render()
    {
        $users = $this->findUsers();

        return view('livewire.users-table', [
            'tableData' => $this->formatTableData($users),
            'pagination' => $users,
        ]);
    }

    protected function findUsers()
    {
        return User::select('id', 'name', 'role', 'deactivated_at')
            ->search($this->filters)
            ->orderBy($this->filters['order'])
            ->paginate(10);
    }

    protected function formatTableData($data)
    {
        return [
            'header' => [__('hiko.name'), __('hiko.role'), __('hiko.status')],
            'rows' => $data->map(function ($user) {
                return [
                    [
                        'label' => $user->name,
                        'link' => route('users.edit', $user->id),
                    ],
                    [
                        'label' => __("hiko.{$user->role}"),
                    ],
                    [
                        'label' => $user->isDeactivated() ? __('hiko.inactive') : __('hiko.active'),
                    ],
                ];
            })
                ->toArray(),
        ];
    }
}
