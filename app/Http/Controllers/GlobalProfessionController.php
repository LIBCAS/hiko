<?php

namespace App\Http\Controllers;

use App\Models\GlobalProfession;
use App\Models\GlobalProfessionCategory;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GlobalProfessionController extends Controller
{
    protected array $rules = [
        'cs' => ['required', 'string', 'max:255'],
        'en' => ['nullable', 'string', 'max:255'],
        'category_id' => ['nullable', 'exists:global_profession_categories,id'],
    ];

    public function index(): View
    {
        $professions = GlobalProfession::with('profession_category')->paginate(20);
        return view('pages.professions.index', [
            'title' => __('hiko.global_professions'),
            'professions' => $professions,
        ]);
    }

    public function create(): View
    {
        $categories = GlobalProfessionCategory::all();
        return view('pages.professions.form', [
            'title' => __('hiko.new_global_profession'),
            'profession' => new GlobalProfession(),
            'action' => route('global.professions.store'),
            'label' => __('hiko.create'),
            'availableCategories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules);

        $professionData = [
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'] ?? null,
            ],
        ];

        // Include category ID only if it is set
        if (isset($validated['category_id'])) {
            $professionData['profession_category_id'] = $validated['category_id'];
        }

        $profession = GlobalProfession::create($professionData);

        return redirect()
            ->route('global.profession.edit', $profession->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(GlobalProfession $globalProfession): View
    {
        $categories = GlobalProfessionCategory::all();
        $globalProfession->load('identities');

        return view('pages.professions.form', [
            'title' => __('hiko.edit_global_profession'),
            'profession' => $globalProfession,
            'action' => route('global.profession.update', $globalProfession->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),
            'availableCategories' => $categories,
        ]);
    }

    public function update(Request $request, GlobalProfession $globalProfession): RedirectResponse
    {
        $validated = $request->validate($this->rules);

        $updateData = [
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'] ?? null,
            ],
        ];

        // Include category ID only if it is set
        if (isset($validated['category_id'])) {
            $updateData['profession_category_id'] = $validated['category_id'];
        }

        $globalProfession->update($updateData);

        return redirect()
            ->route('global.profession.edit', $globalProfession->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(GlobalProfession $globalProfession): RedirectResponse
    {
        $globalProfession->delete();

        return redirect()
            ->route('global.professions')
            ->with('success', __('hiko.removed'));
    }
}
