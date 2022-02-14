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
            'title' => __('Nová kategorie profese'),
            'professionCategory' => new ProfessionCategory,
            'action' => route('professions.category.store'),
            'label' => __('Vytvořit'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules);

        $professionCategory = ProfessionCategory::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        return redirect()->route('professions.category.edit', $professionCategory->id)->with('success', __('Uloženo.'));
    }

    public function edit(ProfessionCategory $professionCategory)
    {
        return view('pages.professions-categories.form', [
            'title' => __('Kategorie profese č. ') . $professionCategory->id,
            'professionCategory' => $professionCategory,
            'method' => 'PUT',
            'action' => route('professions.category.update', $professionCategory),
            'label' => __('Upravit'),
        ]);
    }

    public function update(Request $request, ProfessionCategory $professionCategory)
    {
        $validated = $request->validate($this->rules);

        $professionCategory->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        return redirect()->route('professions.category.edit', $professionCategory->id)->with('success', __('Uloženo.'));
    }

    public function destroy(ProfessionCategory $professionCategory)
    {
        $professionCategory->delete();

        return redirect()->route('professions')->with('success', __('Odstraněno'));
    }

    public function export()
    {
        return Excel::download(new ProfessionCategoriesExport, 'profession-categories.xlsx');
    }
}
