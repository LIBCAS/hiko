<?php

namespace App\Http\Controllers;

use App\Models\Identity;
use App\Models\Profession;
use App\Exports\IdentitiesExport;
use App\Models\ProfessionCategory;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\IdentityRequest;

class IdentityController extends Controller
{
    public function index()
    {
        return view('pages.identities.index', [
            'title' => __('hiko.identities'),
            'labels' => $this->getTypes(),
        ]);
    }

    public function create()
    {
        return view(
            'pages.identities.form',
            array_merge(
                [
                    'title' => __('hiko.new_identity'),
                    'action' => route('identities.store'),
                    'label' => __('hiko.create'),
                    'canRemove' => false,
                    'canMerge' => false,
                ],
                $this->viewData(new Identity)
            )
        );
    }

    public function store(IdentityRequest $request)
    {
        $redirectRoute = $request->action === 'create' ? 'identities.create' : 'identities.edit';

        $validated = $request->validated();

        $identity = Identity::create($validated);

        $this->attachProfessionsAndCategories($identity, $validated);

        return redirect()
            ->route($redirectRoute, $identity->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(Identity $identity)
    {
        $hasLetters = $identity->letters->isNotEmpty();

        return view(
            'pages.identities.form',
            array_merge(
                [
                    'title' => __('hiko.identity') . ': ' . $identity->id,
                    'method' => 'PUT',
                    'action' => route('identities.update', $identity),
                    'label' => __('hiko.edit'),
                    'canRemove' => !$hasLetters,
                    'canMerge' => $hasLetters,
                ],
                $this->viewData($identity)
            )
        );
    }

    public function update(IdentityRequest $request, Identity $identity)
    {
        $redirectRoute = $request->action === 'create' ? 'identities.create' : 'identities.edit';

        $validated = $request->validated();

        $identity->update($validated);

        $this->attachProfessionsAndCategories($identity, $validated);

        return redirect()
            ->route($redirectRoute, $identity->id)
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

    protected function viewData(Identity $identity)
    {
        return [
            'identity' => $identity,
            'types' => $this->getTypes(),
            'selectedType' => $this->getSelectedType($identity),
            'selectedProfessions' => $this->getSelectedProfessions($identity),
            'selectedCategories' => $this->getSelectedCategories($identity),
        ];
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
