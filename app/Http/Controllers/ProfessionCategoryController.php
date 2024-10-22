<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\ProfessionCategory;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProfessionCategoriesExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Models\Profession;
use App\Models\GlobalProfession;

class ProfessionCategoryController extends Controller
{
    protected array $rules = [
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
    
        // Check if we're using a tenant-specific or global context
        $availableProfessions = tenancy()->initialized
            ? Profession::all()  // Use tenant-specific professions table
            : GlobalProfession::all();  // Use global professions table
    
        return view('pages.professions-categories.form', [
            'title' => __('hiko.professions_category') . ': ' . $professionCategory->id,
            'professionCategory' => $professionCategory,
            'method' => 'PUT',
            'action' => route('professions.category.update', $professionCategory),
            'label' => __('hiko.edit'),
            'professions' => $professionCategory->professions,
            'availableProfessions' => $availableProfessions,
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

    public function storeAttachedProfession(Request $request, ProfessionCategory $category): RedirectResponse
    {
        // Validate profession IDs
        $validated = $request->validate([
            'profession_ids' => 'required|array',
            'profession_ids.*' => tenancy()->initialized
                ? 'exists:' . tenancy()->tenant->table_prefix . '__professions,id'
                : 'exists:global_professions,id',
        ]);
    
        // Sync without detaching existing professions
        $category->professions()->syncWithoutDetaching($validated['profession_ids']);
    
        // Redirect back to the edit page with a success message
        return redirect()
            ->route('professions.category.edit', $category->id)
            ->with('success', __('hiko.professions_attached_successfully'));
    }       
}
