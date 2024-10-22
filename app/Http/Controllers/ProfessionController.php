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
    // Move the common rules that do not depend on tenancy outside
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
        // Add the dynamic category validation rule based on tenancy status
        $categoryRule = tenancy()->initialized
            ? 'exists:' . tenancy()->tenant->table_prefix . '__profession_categories,id'
            : 'exists:global_profession_categories,id';

        // Merge base rules with the dynamic category rule
        $rules = array_merge($this->baseRules, [
            'category' => ['nullable', $categoryRule],
        ]);

        // Validate the request
        $validated = $request->validate($rules);

        // Create a new profession with the tenant-specific or global model
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

        // Attach category if provided
        if (isset($validated['category'])) {
            $categoryModel = tenancy()->initialized ? ProfessionCategory::class : GlobalProfessionCategory::class;
            $category = $categoryModel::find($validated['category']);
            $profession->profession_category()->associate($category);
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
            'title' => __('hiko.edit_profession'),
            'profession' => $profession,
            'action' => route('professions.update', $profession->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),
            'availableCategories' => $availableCategories,
        ]);
    }

    public function update(Request $request, Profession $profession): RedirectResponse
    {
        // Add the dynamic category validation rule based on tenancy status
        $categoryRule = tenancy()->initialized
            ? 'exists:' . tenancy()->tenant->table_prefix . '__profession_categories,id'
            : 'exists:global_profession_categories,id';

        // Merge base rules with the dynamic category rule
        $rules = array_merge($this->baseRules, [
            'category' => ['nullable', $categoryRule],
        ]);

        // Validate the request
        $validated = $request->validate($rules);

        // Update profession's name
        $profession->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        // Dissociate the current category
        $profession->profession_category()->dissociate();

        // Associate new category if provided
        if (isset($validated['category'])) {
            $categoryModel = tenancy()->initialized ? ProfessionCategory::class : GlobalProfessionCategory::class;
            $category = $categoryModel::find($validated['category']);
            $profession->profession_category()->associate($category);
        }

        return redirect()
            ->route('professions.edit', $profession->id)
            ->with('success', __('hiko.saved'));
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
