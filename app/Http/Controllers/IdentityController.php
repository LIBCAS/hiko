<?php

namespace App\Http\Controllers;

use App\Models\Identity;
use Illuminate\Http\Request;

class IdentityController extends Controller
{
    public function index()
    {
        return view('pages.identities.index', [
            'title' => __('Lidé a instituce'),
            'labels' => $this->getTypes(),
        ]);
    }

    public function create()
    {
        return view('pages.identities.form', [
            'title' => __('Nová osoba / instituce'),
            'identity' => new Identity(),
            'action' => route('identities.store'),
            'label' => __('Vytvořit'),
            'types' => $this->getTypes(),
        ]);
    }

    public function store(Request $request)
    {
    }

    public function edit(Identity $identity)
    {
        return view('pages.identities.form', [
            'title' => __('Nová osoba / instituce'),
            'identity' => $identity,
            'method' => 'PUT',
            'action' => route('identities.update', $identity),
            'label' => __('Upravit'),
            'types' => $this->getTypes(),
        ]);
    }

    public function update(Request $request, Identity $identity)
    {
    }

    public function destroy(Identity $identity)
    {
    }

    protected function getTypes()
    {
        return [
            'person' => __('Osoba'),
            'institute' => __('Instituce'),
        ];
    }
}
