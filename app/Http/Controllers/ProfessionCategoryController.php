<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProfessionCategory;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProfessionCategoriesExport;

class ProfessionCategoryController extends Controller
{
    protected $rules = [
        'cs' => ['max:255', 'required_without:en'],
        'en' => ['max:255', 'required_without:cs'],
    ];

    public function create()
    {
        return view('pages.professions-categories.form', [
            'title' => __('hiko.new_professions_category'),
            'professionCategory' => new ProfessionCategory,
            'action' => route('professions.category.store'),
            'label' => __('hiko.create'),
        ]);
    }

    public function store(Request $request)
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

    public function edit(ProfessionCategory $professionCategory)
    {
        return view('pages.professions-categories.form', [
            'title' => __('hiko.professions_category') . ': ' . $professionCategory->id,
            'professionCategory' => $professionCategory,
            'method' => 'PUT',
            'action' => route('professions.category.update', $professionCategory),
            'label' => __('hiko.edit'),
        ]);
    }

    public function update(Request $request, ProfessionCategory $professionCategory)
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

    public function destroy(ProfessionCategory $professionCategory)
    {
        $professionCategory->delete();

        return redirect()
            ->route('professions')
            ->with('success', __('hiko.removed'));
    }

    public function export()
    {
        return Excel::download(new ProfessionCategoriesExport, 'profession-categories.xlsx');
    }
}
