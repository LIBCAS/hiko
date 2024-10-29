<?php

namespace App\Http\Controllers;

use App\Models\ProfessionCategory;
use App\Models\GlobalProfessionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfessionCategoryController extends Controller
{
    protected array $baseRules = [
        'cs' => ['max:255', 'required_without:en'],
        'en' => ['max:255', 'required_without:cs'],
    ];

    public function create(): View
    {
        return view('pages.professions-categories.form', [
            'title' => __('hiko.new_professions_category'),
            'professionCategory' => new ProfessionCategory,
            'action' => route('professions.category.store'),
            'label' => __('hiko.create'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->baseRules);

        if (tenancy()->initialized) {
            $professionCategory = ProfessionCategory::create([
                'name' => [
                    'cs' => $validated['cs'],
                    'en' => $validated['en'],
                ],
            ]);
        } else {
            $professionCategory = GlobalProfessionCategory::create([
                'name' => [
                    'cs' => $validated['cs'],
                    'en' => $validated['en'],
                ],
            ]);
        }

        return redirect()
            ->route('professions.category.edit', $professionCategory->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit($professionCategory): View
    {
        if (tenancy()->initialized) {
            $professionCategory = ProfessionCategory::findOrFail($professionCategory);
        } else {
            $professionCategory = GlobalProfessionCategory::findOrFail($professionCategory);
        }

        return view('pages.professions-categories.form', [
            'title' => __('hiko.edit_professions_category'),
            'professionCategory' => $professionCategory,
            'action' => route('professions.category.update', $professionCategory->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),
        ]);
    }

    public function update(Request $request, $professionCategory): RedirectResponse
    {
        $validated = $request->validate($this->baseRules);

        if (tenancy()->initialized) {
            $professionCategory = ProfessionCategory::findOrFail($professionCategory);
        } else {
            $professionCategory = GlobalProfessionCategory::findOrFail($professionCategory);
        }

        $professionCategory->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        return redirect()
            ->route('professions.category.edit', $professionCategory->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy($professionCategory): RedirectResponse
    {
        if (tenancy()->initialized) {
            $professionCategory = ProfessionCategory::findOrFail($professionCategory);
            $professionCategory->delete();
        } else {
            abort(403);
        }

        return redirect()
            ->route('professions')
            ->with('success', __('hiko.removed'));
    }
}
