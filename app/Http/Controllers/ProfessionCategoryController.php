<?php

namespace App\Http\Controllers;

use App\Models\ProfessionCategory;
use Illuminate\Http\Request;

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
            'professionCategory' => new ProfessionCategory(),
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
            'title' => __('Kategorie profese: '),
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
}