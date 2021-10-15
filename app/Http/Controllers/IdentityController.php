<?php

namespace App\Http\Controllers;

use App\Models\Identity;
use App\Models\Profession;
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
        $identity = new Identity();

        return view('pages.identities.form', [
            'title' => __('Nová osoba / instituce'),
            'identity' => $identity,
            'action' => route('identities.store'),
            'label' => __('Vytvořit'),
            'types' => $this->getTypes(),
            'selectedProfessions' => $this->getProfessions($identity),
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
            'selectedProfessions' => $this->getProfessions($identity),
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
            'institution' => __('Instituce'),
        ];
    }

    protected function getProfessions(Identity $identity)
    {
        if (request()->old('profession')) {
            $professions = Profession::whereIn('id', request()->old('profession'))->get();

            return $$professions->map(function ($profession) {
                return [
                    'id' => $profession->id,
                    'name' => implode(' | ', array_values($profession->getTranslations('name'))),
                ];
            });
        }

        if ($identity->professions) {
            return $identity->professions->map(function ($profession) {
                return [
                    'id' => $profession->id,
                    'name' => implode(' | ', array_values($profession->getTranslations('name'))),
                ];
            });
        }
    }
}
