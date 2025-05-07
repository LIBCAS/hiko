<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
        'role' => ['required', 'string'],
        'deactivated_at' => [],
    ];

    public function index()
    {
        return view('pages.users.index', [
            'title' => __('hiko.users'),
            'roles' => ['admin', 'editor', 'guest', 'developer', 'contributor'],
        ]);
    }

    public function create()
    {
        return view('pages.users.form', [
            'title' => __('hiko.new_account'),
            'user' => new User,
            'action' => route('users.store'),
            'label' => __('hiko.create'),
            'roles' => ['admin', 'editor', 'guest', 'developer', 'contributor'],
            'editEmail' => true,
            'editStatus' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userTable = (new User)->getTable();

        $validated = $request->validate(array_merge($this->rules, [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique($userTable),
            ],
        ]));

        $user = User::create($validated);

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
            'roles' => ['admin', 'editor', 'guest', 'developer', 'contributor'],
            'editEmail' => false,
            'editStatus' => true,
            'active' => empty($user->deactivated_at),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
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

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()
            ->route('users')
            ->with('success', __('hiko.removed'));
    }
}
