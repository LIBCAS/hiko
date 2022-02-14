<?php

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UsersTable extends Component
{
    use WithPagination;

    public $filters = [];

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
        $query = User::select('id', 'name', 'role', 'deactivated_at');

        if (isset($this->filters['name']) && !empty($this->filters['name'])) {
            $query->where('name', 'LIKE', "%" . $this->filters['name'] . "%");
        }

        if (isset($this->filters['role']) && !empty($this->filters['role'])) {
            $query->where('role', '=', $this->filters['role']);
        }

        if (isset($this->filters['status'])) {
            if ($this->filters['status'] === '1') {
                $query->where('deactivated_at', '=', null);
            } else if ($this->filters['status'] === '0') {
                $query->where('deactivated_at', '!=', null);
            }
        }

        return $query->paginate(10);
    }

    protected function formatTableData($data)
    {
        return [
            'header' => ['Jméno', 'Role', 'Status'],
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
            })->toArray(),
        ];
    }
}
