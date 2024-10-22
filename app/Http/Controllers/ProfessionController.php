<?php

namespace App\Http\Controllers;

use App\Models\Profession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\ProfessionsExport;
use App\Models\ProfessionCategory;
use App\Models\GlobalProfession;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfessionController extends Controller
{
    protected array $rules = [
        'cs' => ['max:255', 'required_without:en'],
        'en' => ['max:255', 'required_without:cs'],
        'category' => ['nullable', 'exists:profession_categories,id'],
    ];

    public function index(): View
    {
        // Fetch all tenant-specific and global professions
        $professions = Profession::all()->concat(DB::table('global_professions')->get());

        return view('pages.professions.index', [
            'title' => __('hiko.professions'),
            'professions' => $professions,
        ]);
    }

    public function create(): View
    {
        // Display form for creating a tenant-specific profession
        return view('pages.professions.form', [
            'title' => __('hiko.new_profession'),
            'profession' => new Profession,
            'action' => route('professions.store'),
            'label' => __('hiko.create'),
            'category' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules);
        $profession = Profession::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);
    
        // Update to handle tenant-specific or global category association
        if (isset($validated['category'])) {
            $profession->profession_category()->associate($validated['category']);
        }
    
        return redirect()
            ->route('professions.edit', $profession->id)
            ->with('success', __('hiko.saved'));
    }    

    public function edit(Profession $profession): View
    {
        $profession->load('identities');

        return view('pages.professions.form', [
            'title' => __('hiko.edit_profession'),
            'profession' => $profession,
            'action' => route('professions.update', $profession->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),
            'category' => null,
        ]);
    }

    public function update(Request $request, Profession $profession): RedirectResponse
    {
        $validated = $request->validate($this->rules);
        $profession->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        // Update the association of the profession with tenant-specific or global categories
        $profession->profession_category()->dissociate();
        if (isset($validated['category'])) {
            $profession->profession_category()->associate($validated['category']);
        }

        return redirect()
            ->route('professions.edit', $profession->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(Profession $profession): RedirectResponse
    {
        $profession->delete();

        return redirect()
            ->route('professions')
            ->with('success', __('hiko.removed'));
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new ProfessionsExport, 'professions.xlsx');
    }
}
