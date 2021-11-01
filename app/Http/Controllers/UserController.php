<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Notifications\NewUserPasswordCreate;

class UserController extends Controller
{
    protected $rules = [
        'name' => ['required', 'string', 'max:255'],
        'role' => ['required', 'string'],
        'deactivated_at' => [],
    ];

    public function index()
    {
        return view('pages.users.index', [
            'title' => __('Uživatelé'),
            'roles' => $this->getRoles(),
        ]);
    }

    public function create()
    {
        return view('pages.users.form', [
            'title' => 'Nový účet',
            'user' => new User(),
            'action' => route('users.store'),
            'label' => __('Vytvořit'),
            'roles' => $this->getRoles(),
            'editEmail' => true,
            'editStatus' => false,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(array_merge($this->rules, [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        ]));

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => bcrypt(Str::random(10)),
        ]);

        $user->notify(new NewUserPasswordCreate($user));

        return redirect()->route('users.edit', $user->id)->with('success', __('Uloženo.'));
    }

    public function edit(User $user)
    {
        return view('pages.users.form', [
            'title' => 'Účet: ' . $user->name,
            'user' => $user,
            'action' => route('users.update', $user),
            'method' => 'PUT',
            'label' => __('Upravit'),
            'roles' => $this->getRoles(),
            'editEmail' => false,
            'editStatus' => true,
            'active' => empty($user->deactivated_at) ? true : false,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate($this->rules);

        $user->update([
            'name' => $validated['name'],
            'role' => $validated['role'],
            'deactivated_at' => isset($validated['deactivated_at']) && $validated['deactivated_at'] === 'on' ? null : now()->format('Y-m-d H:i:s'),
        ]);

        return redirect()->route('users.edit', $user->id)->with('success', __('Uloženo.'));
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users')->with('success', 'Odstraněno');
    }

    protected function getRoles()
    {
        return [
            'admin' => __('Správce'),
            'editor' => __('Editor'),
            'guest' => __('Divák'),
        ];
    }
}
