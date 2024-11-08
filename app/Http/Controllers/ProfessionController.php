<?php

namespace App\Http\Controllers;

use App\Models\Profession;
use App\Models\GlobalProfession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\ProfessionsExport;
use App\Models\ProfessionCategory;
use App\Models\GlobalProfessionCategory;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfessionController extends Controller
{
    protected array $baseRules = [
        'cs' => ['max:255', 'required_without:en'],
        'en' => ['max:255', 'required_without:cs'],
    ];

    public function index(): View
    {
        // Fetch both tenant-specific and global professions
        $professions = tenancy()->initialized
            ? Profession::all()
            : GlobalProfession::all();

        return view('pages.professions.index', [
            'title' => __('hiko.professions'),
            'professions' => $professions,
        ]);
    }

    public function create(): View
    {
        // Get available categories based on whether tenancy is initialized
        $availableCategories = tenancy()->initialized
            ? ProfessionCategory::all()
            : GlobalProfessionCategory::all();

        return view('pages.professions.form', [
            'title' => __('hiko.new_profession'),
            'profession' => new Profession,
            'action' => route('professions.store'),
            'label' => __('hiko.create'),
            'availableCategories' => $availableCategories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Determine the category validation rule based on tenancy status
        $categoryRule = tenancy()->initialized
            ? 'exists:' . tenancy()->tenant->table_prefix . '__profession_categories,id'
            : 'exists:global_profession_categories,id';
        
        // Validate the input fields
        $validated = $request->validate(array_merge($this->baseRules, [
            'category' => ['nullable', $categoryRule],
        ]));
        
        // Create a new profession entry based on tenancy
        $profession = tenancy()->initialized
            ? Profession::create([
                'name' => [
                    'cs' => $validated['cs'],
                    'en' => $validated['en'],
                ],
            ])
            : GlobalProfession::create([
                'name' => [
                    'cs' => $validated['cs'],
                    'en' => $validated['en'],
                ],
            ]);
        
        // Attach the selected category, only if it matches the profession type
        if (isset($validated['category'])) {
            $categoryModel = tenancy()->initialized ? ProfessionCategory::class : GlobalProfessionCategory::class;
            $category = $categoryModel::find($validated['category']);
            
            // Check if category matches the profession type
            if ((tenancy()->initialized && $category instanceof ProfessionCategory) ||
                (!tenancy()->initialized && $category instanceof GlobalProfessionCategory)) {
                $profession->profession_category()->associate($category)->save();
            } else {
                return redirect()->back()->withErrors(['category' => __('Invalid category selection for this profession type.')]);
            }
        };
        
        return redirect()
            ->route('professions.edit', $profession->id)
            ->with('success', __('hiko.saved'));
    }
    
    public function update(Request $request, Profession $profession): RedirectResponse
    {
        $categoryRule = tenancy()->initialized
            ? 'exists:' . tenancy()->tenant->table_prefix . '__profession_categories,id'
            : 'exists:global_profession_categories,id';
        
        $validated = $request->validate(array_merge($this->baseRules, [
            'category' => ['nullable', $categoryRule],
        ]));
        
        $profession->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);
        
        // Disassociate any existing category first
        $profession->profession_category()->dissociate();
        
        // Re-attach the selected category if it matches the profession type
        if (isset($validated['category'])) {
            $categoryModel = tenancy()->initialized ? ProfessionCategory::class : GlobalProfessionCategory::class;
            $category = $categoryModel::find($validated['category']);
            
            if ((tenancy()->initialized && $category instanceof ProfessionCategory) ||
                (!tenancy()->initialized && $category instanceof GlobalProfessionCategory)) {
                $profession->profession_category()->associate($category)->save();
            } else {
                return redirect()->back()->withErrors(['category' => __('Invalid category selection for this profession type.')]);
            }
        }
        
        return redirect()
            ->route('professions.edit', $profession->id)
            ->with('success', __('hiko.saved'));
    }    
    
    public function edit(Profession $profession): View
    {
        // Load related identities
        $profession->load('identities');

        // Fetch categories based on tenancy status
        $availableCategories = tenancy()->initialized
            ? ProfessionCategory::all()
            : GlobalProfessionCategory::all();

        return view('pages.professions.form', [
            'title' => __('hiko.profession'),
            'profession' => $profession,
            'action' => route('professions.update', $profession->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),
            'availableCategories' => $availableCategories,
        ]);
    }

    public function destroy(Profession $profession): RedirectResponse
    {
        // Delete the profession
        $profession->delete();

        return redirect()
            ->route('professions')
            ->with('success', __('hiko.removed'));
    }

    public function export(): BinaryFileResponse
    {
        // Export the professions to an Excel file
        return Excel::download(new ProfessionsExport, 'professions.xlsx');
    }
}
