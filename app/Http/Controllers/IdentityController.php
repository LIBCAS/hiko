<?php

namespace App\Http\Controllers;

use App\Exports\IdentitiesExport;
use App\Models\Identity;
use App\Models\Profession;
use Illuminate\Http\Request;
use App\Models\ProfessionCategory;
use Maatwebsite\Excel\Facades\Excel;

class IdentityController extends Controller
{
    protected $person_rules = [
        'surname' => ['required', 'string', 'max:255'],
        'forename' => ['nullable', 'string', 'max:255'],
        'birth_year' => ['nullable', 'string', 'max:255'],
        'death_year' => ['nullable', 'string', 'max:255'],
        'nationality' => ['nullable', 'string', 'max:255'],
        'gender' => ['nullable', 'string', 'max:255'],
        'note' => ['nullable'],
        'viaf_id' => ['nullable', 'integer', 'numeric'],
        'type' => ['required', 'string', 'max:255'],
        'category' => ['nullable', 'exists:profession_categories,id'],
        'profession' => ['nullable', 'exists:professions,id'],
    ];

    protected $institution_rules = [
        'name' => ['required', 'string', 'max:255'],
        'note' => ['nullable'],
        'viaf_id' => ['nullable', 'integer', 'numeric'],
        'type' => ['required', 'string', 'max:255'],
    ];

    public function index()
    {
        return view('pages.identities.index', [
            'title' => __('Lidé a instituce'),
            'labels' => $this->getTypes(),
        ]);
    }

    public function create()
    {
        $identity = new Identity;

        return view('pages.identities.form', [
            'title' => __('Nová osoba / instituce'),
            'identity' => $identity,
            'action' => route('identities.store'),
            'label' => __('Vytvořit'),
            'types' => $this->getTypes(),
            'selectedProfessions' => $this->getProfessions($identity),
            'selectedCategories' => $this->getCategories($identity),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        $identity = Identity::create($validated);

        $this->attachProfessionsAndCategories($identity, $validated);

        return redirect()->route('identities.edit', $identity->id)->with('success', __('Uloženo.'));
    }

    public function edit(Identity $identity)
    {
        return view('pages.identities.form', [
            'title' => __('Osoba / instituce č. ') . $identity->id,
            'identity' => $identity,
            'method' => 'PUT',
            'action' => route('identities.update', $identity),
            'label' => __('Upravit'),
            'types' => $this->getTypes(),
            'selectedProfessions' => $this->getProfessions($identity),
            'selectedCategories' => $this->getCategories($identity),
        ]);
    }

    public function update(Request $request, Identity $identity)
    {
        $validated = $this->validateRequest($request);

        $identity->update($validated);

        $this->attachProfessionsAndCategories($identity, $validated);

        return redirect()->route('identities.edit', $identity->id)->with('success', __('Uloženo.'));
    }

    public function destroy(Identity $identity)
    {
        $identity->delete();

        return redirect()->route('identities')->with('success', __('Odstraněno'));
    }

    public function export()
    {
        return Excel::download(new IdentitiesExport, 'identities.xlsx');
    }

    protected function validateRequest(Request $request)
    {
        $validated = null;

        if ($request->type === 'institution') {
            $validated = $request->validate($this->institution_rules);
        }

        if ($request->type === 'person') {
            // odstraní null, nutné pro správnou validaci
            $category = empty($request->category) ? null : array_filter($request->category);
            $request->request->set('category', empty($category) ? null : $category);

            $profession = empty($request->profession) ? null : array_filter($request->profession);
            $request->request->set('profession', empty($profession) ? null : $profession);

            $validated = $request->validate($this->person_rules);
            $name = $validated['surname'];
            $name .= $validated['forename'] ? ', ' . $validated['forename'] : '';
            $validated['name'] = $name;
        }

        return $validated;
    }

    protected function attachProfessionsAndCategories(Identity $identity, $validated)
    {
        $identity->professions()->detach();
        $identity->profession_categories()->detach();

        if (isset($validated['profession']) && !empty($validated['profession'])) {
            collect($validated['profession'])->each(function ($profession, $index) use ($identity) {
                $identity->professions()->attach($profession, ['position' => $index]);
            });
        }

        if (isset($validated['category']) && !empty($validated['category'])) {
            collect($validated['category'])->each(function ($category, $index) use ($identity) {
                $identity->profession_categories()->attach($category, ['position' => $index]);
            });
        }
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
            $ids = request()->old('profession');
            $professions = Profession::whereIn('id', $ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
                ->get();

            return $professions->map(function ($profession) {
                return [
                    'id' => $profession->id,
                    'name' => implode(' | ', array_values($profession->getTranslations('name'))),
                ];
            });
        }

        if ($identity->professions) {
            return $identity->professions
                ->sortBy('pivot.position')
                ->map(function ($profession) {
                    return [
                        'id' => $profession->id,
                        'name' => implode(' | ', array_values($profession->getTranslations('name'))),
                    ];
                })
                ->values()
                ->toArray();
        }
    }

    protected function getCategories(Identity $identity)
    {
        if (request()->old('category')) {
            $ids = request()->old('category');
            $categories = ProfessionCategory::whereIn('id', $ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
                ->get();

            return $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => implode(' | ', array_values($category->getTranslations('name'))),
                ];
            });
        }

        if ($identity->profession_categories) {
            return $identity->profession_categories
                ->sortBy('pivot.position')
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => implode(' | ', array_values($category->getTranslations('name'))),
                    ];
                })
                ->values()
                ->toArray();
        }
    }
}
