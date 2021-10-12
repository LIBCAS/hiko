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
    }

    public function store(Request $request)
    {
    }

    public function edit(Identity $identity)
    {
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
