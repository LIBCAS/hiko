<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Requests\ProfessionCategoryRequest;
use App\Models\ProfessionCategory;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProfessionCategoriesExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Models\Profession;
use App\Models\GlobalProfession;

class ProfessionCategoryController extends Controller
{
    public function create()
    {
        return view('pages.professions-categories.form', [
            'title' => __('hiko.new_professions_category'),
            'professionCategory' => new ProfessionCategory,
            'action' => route('professions.category.store'),
            'label' => __('hiko.create'),
        ]);
    }

    public function store(ProfessionCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->failsDuplicateCheck()) {
        return redirect()
            ->back()
            ->withErrors(['cs' => __('hiko.entity_already_exists')])
            ->withInput();
        }

        $professionCategory = ProfessionCategory::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ]
        ]);

        return redirect()
            ->route('professions.category.edit', $professionCategory->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(ProfessionCategory $professionCategory)
    {
        $professionCategory->load('identities', 'professions');
        $availableProfessions = tenancy()->initialized ? Profession::all() : GlobalProfession::all();
        $professions = $professionCategory->professions;

        return view('pages.professions-categories.form', [
            'title' => __('hiko.professions_category'),
            'professionCategory' => $professionCategory,
            'action' => route('professions.category.update', $professionCategory->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),  // Button label set to "Save" for edit action
            'availableProfessions' => $availableProfessions,
            'professions' => $professions,
        ]);
    }

    public function update(ProfessionCategoryRequest $request, ProfessionCategory $professionCategory): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->failsDuplicateCheck($professionCategory->id)) {
            return redirect()
                ->back()
                ->withErrors(['cs' => __('hiko.entity_already_exists')])
                ->withInput();
        }

        $professionCategory->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ]
        ]);

        return redirect()
            ->route('professions.category.edit', $professionCategory->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(ProfessionCategory $professionCategory): RedirectResponse
    {
        $professionCategory->delete();

        return redirect()
            ->route('professions')
            ->with('success', __('hiko.removed'));
    }
}
