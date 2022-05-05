<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

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
            'title' => __('hiko.users'),
            'roles' => $this->getRoles(),
        ]);
    }

    public function create()
    {
        return view('pages.users.form', [
            'title' => __('hiko.new_account'),
            'user' => new User,
            'action' => route('users.store'),
            'label' => __('hiko.create'),
            'roles' => $this->getRoles(),
            'editEmail' => true,
            'editStatus' => false,
        ]);
    }

    public function store(Request $request)
    {
        $user = User::create($request->validate(array_merge($this->rules, [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        ])));

        return redirect()
            ->route('users.edit', $user->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(User $user)
    {
        return view('pages.users.form', [
            'title' => __('hiko.account') . ': ' . $user->name,
            'user' => $user,
            'action' => route('users.update', $user),
            'method' => 'PUT',
            'label' => __('hiko.edit'),
            'roles' => $this->getRoles(),
            'editEmail' => false,
            'editStatus' => true,
            'active' => empty($user->deactivated_at) ? true : false,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate($this->rules);
        $validated['deactivated_at'] = isset($validated['deactivated_at']) && $validated['deactivated_at'] === 'on'
            ? null
            : now()->format('Y-m-d H:i:s');

        $user->update($validated);

        return redirect()
            ->route('users.edit', $user->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('users')
            ->with('success', __('hiko.removed'));
    }

    protected function getRoles()
    {
        return ['admin', 'editor', 'guest', 'developer'];
    }
}
