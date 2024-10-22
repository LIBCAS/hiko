<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\ProfessionCategory;
use App\Models\GlobalProfessionCategory;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProfessionCategoriesExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfessionCategoryController extends Controller
{
    protected array $rules = [
        'cs' => ['max:255', 'required_without:en'],
        'en' => ['max:255', 'required_without:cs'],
    ];

    public function create(): View
    {
        // Display form for creating a tenant-specific profession category
        return view('pages.professions-categories.form', [
            'title' => __('hiko.new_professions_category'),
            'professionCategory' => new ProfessionCategory,
            'action' => route('professions.category.store'),
            'label' => __('hiko.create'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Validate and store tenant-specific category
        $validated = $request->validate($this->rules);
        $professionCategory = ProfessionCategory::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        return redirect()
            ->route('professions.category.edit', $professionCategory->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(ProfessionCategory $professionCategory): View
    {
        $professionCategory->load('professions');

        // Pass $canAttachProfessions for user-specific permissions
        $canAttachProfessions = auth()->user()->can('attach-professions', $professionCategory);

        // Check if local or global connection
        return view('pages.professions-categories.form', [
            'title' => __('hiko.professions_category') . ': ' . $professionCategory->id,
            'professionCategory' => $professionCategory,
            'method' => 'PUT',
            'action' => route('professions.category.update', $professionCategory),
            'label' => __('hiko.edit'),
            'professions' => $professionCategory->professions,
            'availableProfessions' => $professionCategory->getConnectionName() === 'mysql'
                ? GlobalProfessionCategory::all() // Global categories
                : ProfessionCategory::all(), // Tenant-specific categories
            'canAttachProfessions' => $canAttachProfessions,
        ]);
    }

    public function update(Request $request, ProfessionCategory $professionCategory): RedirectResponse
    {
        $validated = $request->validate($this->rules);
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

    public function destroy(ProfessionCategory $professionCategory): RedirectResponse
    {
        $professionCategory->delete();

        return redirect()
            ->route('professions')
            ->with('success', __('hiko.removed'));
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new ProfessionCategoriesExport, 'profession-categories.xlsx');
    }
}
