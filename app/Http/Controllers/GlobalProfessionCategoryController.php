<?php

namespace App\Http\Controllers;

use App\Models\GlobalProfessionCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class GlobalProfessionCategoryController extends Controller
{
    protected array $rules = [
        'name.cs' => ['required', 'string', 'max:255'],
        'name.en' => ['nullable', 'string', 'max:255'],
    ];

    public function create(): View
    {
        return view('admin.global_profession_categories.form', [
            'title' => __('Create Global Profession Category'),
            'action' => route('global-profession-categories.store'),
            'label' => __('hiko.create'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules);

        // Use the 'name' attribute for translations, providing CS and EN as an array
        $globalProfessionCategory = GlobalProfessionCategory::create([
            'name' => [
                'cs' => $validated['name']['cs'],
                'en' => $validated['name']['en'] ?? null,
            ]
        ]);

        return redirect()
            ->route('global-profession-categories.edit', $globalProfessionCategory->id)
            ->with('success', __('Global Profession Category created successfully.'));
    }

    public function edit(GlobalProfessionCategory $globalProfessionCategory): View
    {
        return view('admin.global_profession_categories.form', [
            'title' => __('Edit Global Profession Category'),
            'professionCategory' => $globalProfessionCategory,
            'action' => route('global-profession-categories.update', $globalProfessionCategory->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),
            'delete' => route('global-profession-categories.destroy', $globalProfessionCategory->id),
        ]);
    }

    public function update(Request $request, GlobalProfessionCategory $globalProfessionCategory): RedirectResponse
    {
        $validated = $request->validate($this->rules);

        // Update the translations in the 'name' attribute
        $globalProfessionCategory->update([
            'name' => [
                'cs' => $validated['name']['cs'],
                'en' => $validated['name']['en'] ?? null,
            ]
        ]);

        return redirect()
            ->route('global-profession-categories.edit', $globalProfessionCategory->id)
            ->with('success', __('Global Profession Category updated successfully.'));
    }

    public function destroy(GlobalProfessionCategory $globalProfessionCategory): RedirectResponse
    {
        $globalProfessionCategory->delete();

        return redirect()
            ->route('global-profession-categories.create')
            ->with('success', __('Global Profession Category deleted successfully.'));
    }
}
