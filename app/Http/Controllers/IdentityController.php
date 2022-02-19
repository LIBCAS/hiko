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
            'title' => __('hiko.identities'),
            'labels' => $this->getTypes(),
        ]);
    }

    public function create()
    {
        $identity = new Identity;

        return view('pages.identities.form', [
            'title' => __('hiko.new_identity'),
            'identity' => $identity,
            'action' => route('identities.store'),
            'label' => __('hiko.create'),
            'types' => $this->getTypes(),
            'selectedType' => $this->getSelectedType($identity),
            'selectedProfessions' => $this->getSelectedProfessions($identity),
            'selectedCategories' => $this->getSelectedCategories($identity),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        $identity = Identity::create($validated);

        $this->attachProfessionsAndCategories($identity, $validated);

        return redirect()
            ->route('identities.edit', $identity->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(Identity $identity)
    {
        return view('pages.identities.form', [
            'title' => __('hiko.identity') . ': ' . $identity->id,
            'identity' => $identity,
            'method' => 'PUT',
            'action' => route('identities.update', $identity),
            'label' => __('hiko.edit'),
            'types' => $this->getTypes(),
            'selectedType' => $this->getSelectedType($identity),
            'selectedProfessions' => $this->getSelectedProfessions($identity),
            'selectedCategories' => $this->getSelectedCategories($identity),
        ]);
    }

    public function update(Request $request, Identity $identity)
    {
        $validated = $this->validateRequest($request);

        $identity->update($validated);

        $this->attachProfessionsAndCategories($identity, $validated);

        return redirect()
            ->route('identities.edit', $identity->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(Identity $identity)
    {
        $identity->delete();

        return redirect()
            ->route('identities')
            ->with('success', __('hiko.removed'));
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
            collect($validated['profession'])
                ->each(function ($profession, $index) use ($identity) {
                    $identity->professions()->attach($profession, ['position' => $index]);
                });
        }

        if (isset($validated['category']) && !empty($validated['category'])) {
            collect($validated['category'])
                ->each(function ($category, $index) use ($identity) {
                    $identity->profession_categories()->attach($category, ['position' => $index]);
                });
        }
    }

    protected function getTypes()
    {
        return ['person', 'institution'];
    }

    protected function getSelectedProfessions(Identity $identity)
    {
        if (!request()->old('profession') && !$identity->professions) {
            return [];
        }

        $professions = request()->old('profession')
            ? Profession::whereIn('id', request()->old('profession'))
            ->orderByRaw('FIELD(id, ' . implode(',', request()->old('profession')) . ')')->get()
            : $identity->professions->sortBy('pivot.position');

        return $professions
            ->map(function ($profession) {
                return [
                    'value' => $profession->id,
                    'label' => $profession->getTranslation('name', config('hiko.metadata_default_locale')),
                ];
            })
            ->toArray();
    }

    protected function getSelectedCategories(Identity $identity)
    {
        if (!request()->old('category') && !$identity->profession_categories) {
            return [];
        }

        $categories = request()->old('category')
            ? ProfessionCategory::whereIn('id', request()->old('category'))
            ->orderByRaw('FIELD(id, ' . implode(',', request()->old('category')) . ')')->get()
            : $identity->profession_categories->sortBy('pivot.position');

        return $categories
            ->map(function ($category) {
                return [
                    'value' => $category->id,
                    'label' => $category->getTranslation('name', config('hiko.metadata_default_locale')),
                ];
            })
            ->toArray();
    }

    protected function getSelectedType(Identity $identity)
    {
        if (!request()->old('type') && !$identity->type) {
            return 'person';
        }

        return request()->old('type')
            ? request()->old('type')
            : $identity->type;
    }
}
