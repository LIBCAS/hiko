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

    public function index(): View
    {
        $categories = GlobalProfessionCategory::with('professions')->get();

        return view('admin.global_profession_categories.index', [
            'title' => __('Global Profession Categories'),
            'categories' => $categories,
        ]);
    }

    public function create(): View
    {
        return view('admin.global_profession_categories.form', [
            'title' => __('Create Global Profession Category'),
            'action' => route('global-profession-categories.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules);
        GlobalProfessionCategory::create($validated);

        return redirect()
            ->route('global-profession-categories.index')
            ->with('success', __('Global Profession Category created successfully.'));
    }

    public function edit(GlobalProfessionCategory $globalProfessionCategory): View
    {
        return view('admin.global_profession_categories.form', [
            'title' => __('Edit Global Profession Category'),
            'professionCategory' => $globalProfessionCategory,
            'action' => route('global-profession-categories.update', $globalProfessionCategory->id),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, GlobalProfessionCategory $globalProfessionCategory): RedirectResponse
    {
        $validated = $request->validate($this->rules);
        $globalProfessionCategory->update($validated);

        return redirect()
            ->route('global-profession-categories.index')
            ->with('success', __('Global Profession Category updated successfully.'));
    }

    public function destroy(GlobalProfessionCategory $globalProfessionCategory): RedirectResponse
    {
        $globalProfessionCategory->delete();

        return redirect()
            ->route('global-profession-categories.index')
            ->with('success', __('Global Profession Category deleted successfully.'));
    }
}
