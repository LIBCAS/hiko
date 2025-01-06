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

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $professions = GlobalProfession::with('profession_category')->paginate(20);
        return view('pages.global-professions', compact('professions'))
            ->with('title', __('hiko.global_professions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $categories = GlobalProfessionCategory::all();
        return view('pages.global-professions.form', [
            'title' => __('hiko.new_global_profession'),
            'profession' => new GlobalProfession(),
            'action' => route('global.professions.store'),
            'label' => __('hiko.create'),
            'availableCategories' => $categories,
        ]);
    }    

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules);
    
        $professionData = [
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'] ?? null,
            ],
            'profession_category_id' => $validated['category_id'] ?? null,
        ];
    
        $profession = GlobalProfession::create($professionData);
    
        // Handle 'action' parameter
        if ($request->input('action') === 'create') {
            return redirect()
                ->route('global.professions.create')
                ->with('success', __('hiko.saved'));
        }
    
        return redirect()
            ->route('global.professions.edit', $profession->id)
            ->with('success', __('hiko.saved'));
    }      

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GlobalProfession $globalProfession): View
    {
        $categories = GlobalProfessionCategory::all();
        $globalProfession->load('profession_category');
    
        return view('pages.global-professions.form', [
            'title' => __('hiko.global_profession'),
            'profession' => $globalProfession,
            'action' => route('global.professions.update', $globalProfession->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),
            'availableCategories' => $categories,
        ]);
    }    
    
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GlobalProfession $globalProfession): RedirectResponse
    {
        $validated = $request->validate($this->rules);
    
        $updateData = [
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'] ?? null,
            ],
            'profession_category_id' => $validated['category_id'] ?? null,
        ];
    
        $globalProfession->update($updateData);
    
        // Handle 'action' parameter
        if ($request->input('action') === 'create') {
            return redirect()
                ->route('global.professions.create')
                ->with('success', __('hiko.saved'));
        }
    
        return redirect()
            ->route('global.professions.edit', $globalProfession->id)
            ->with('success', __('hiko.saved'));
    }       
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GlobalProfession $globalProfession): RedirectResponse
    {
        $globalProfession->delete();
    
        return redirect()
            ->route('professions')
            ->with('success', __('hiko.removed'));
    }      
}
