<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\ProfessionCategory;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProfessionCategoriesExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Log;

class ProfessionCategoryController extends Controller
{
    protected array $rules = [
        'cs' => ['max:255', 'required_without:en'],
        'en' => ['max:255', 'required_without:cs'],
    ];

    public function create(): View
    {
        Log::info('Creating new Profession Category');
        
        return view('pages.professions-categories.form', [
            'title' => __('hiko.new_professions_category'),
            'professionCategory' => new ProfessionCategory,
            'action' => route('professions.category.store'),
            'label' => __('hiko.create'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $redirectRoute = $request->action === 'create' ? 'professions.category.create' : 'professions.category.edit';

        $validated = $request->validate($this->rules);

        $professionCategory = ProfessionCategory::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        return redirect()
            ->route($redirectRoute, $professionCategory->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(ProfessionCategory $professionCategory): View
    {
        $professions = $professionCategory->professions;
        
        return view('pages.professions-categories.form', [
            'title' => __('hiko.professions_category') . ': ' . $professionCategory->id,
            'professionCategory' => $professionCategory,
            'method' => 'PUT',
            'action' => route('professions.category.update', $professionCategory),
            'label' => __('hiko.edit'),
            'professions' => $professions,
        ]);
    }

    public function update(Request $request, ProfessionCategory $professionCategory): RedirectResponse
    {
        $redirectRoute = $request->action === 'create' ? 'professions.category.create' : 'professions.category.edit';

        $validated = $request->validate($this->rules);

        $professionCategory->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        return redirect()
            ->route($redirectRoute, $professionCategory->id)
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
