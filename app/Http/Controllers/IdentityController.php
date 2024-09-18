<?php

namespace App\Http\Controllers;

use App\Models\Identity;
use App\Models\GlobalProfession;
use App\Models\GlobalProfessionCategory;
use App\Exports\IdentitiesExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\IdentityRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class IdentityController extends Controller
{
    public function index(): View
    {
        return view('pages.identities.index', [
            'title' => __('hiko.identities'),
            'labels' => $this->getTypes(),
        ]);
    }

    public function create(): View
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

    public function store(IdentityRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $identity = Identity::create($validated);
        $this->attachProfessionsAndCategories($identity, $validated);

        return redirect()
            ->route('identities.edit', $identity->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(Identity $identity): View
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

    public function update(IdentityRequest $request, Identity $identity): RedirectResponse
    {
        $validated = $request->validated();
        $identity->update($validated);
        $this->attachProfessionsAndCategories($identity, $validated);

        return redirect()
            ->route('identities.edit', $identity->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(Identity $identity): RedirectResponse
    {
        $identity->delete();

        return redirect()
            ->route('identities')
            ->with('success', __('hiko.removed'));
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new IdentitiesExport, 'identities.xlsx');
    }

    protected function viewData(Identity $identity): array
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
        $identity->professionCategories()->detach();

        if (isset($validated['profession']) && !empty($validated['profession'])) {
            collect($validated['profession'])
                ->each(function ($profession, $index) use ($identity) {
                    $identity->professions()->attach($profession, ['position' => $index]);
                });
        }

        if (isset($validated['category']) && !empty($validated['category'])) {
            collect($validated['category'])
                ->each(function ($category, $index) use ($identity) {
                    $identity->professionCategories()->attach($category, ['position' => $index]);
                });
        }
    }

    protected function getTypes(): array
    {
        return ['person', 'institution'];
    }

    protected function getSelectedProfessions(Identity $identity): array
    {
        if (!request()->old('profession') && !$identity->professions) {
            return [];
        }

        $professions = request()->old('profession')
            ? GlobalProfession::whereIn('id', request()->old('profession'))
                ->orderByRaw('FIELD(id, ' . implode(',', request()->old('profession')) . ')')->get()
            : $identity->professions->sortBy('pivot.position');

        return $professions
            ->map(function ($profession) {
                return [
                    'value' => $profession->id,
                    'label' => $profession->name,
                ];
            })
            ->toArray();
    }

    protected function getSelectedCategories(Identity $identity): array
    {
        if (!request()->old('category') && !$identity->professionCategories) {
            return [];
        }

        $categories = request()->old('category')
            ? GlobalProfessionCategory::whereIn('id', request()->old('category'))
                ->orderByRaw('FIELD(id, ' . implode(',', request()->old('category')) . ')')->get()
            : $identity->professionCategories->sortBy('pivot.position');

        return $categories
            ->map(function ($category) {
                return [
                    'value' => $category->id,
                    'label' => $category->name,
                ];
            })
            ->toArray();
    }

    protected function getSelectedType(Identity $identity): string
    {
        return request()->old('type') ? request()->old('type') : $identity->type ?? 'person';
    }
}
