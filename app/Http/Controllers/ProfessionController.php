<?php

namespace App\Http\Controllers;

use App\Exports\ProfessionsExport;
use App\Http\Requests\ProfessionRequest;
use App\Models\Profession;
use App\Models\ProfessionCategory;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfessionController extends Controller
{
    public function index()
    {
        // Fetch both tenant-specific and global professions
        $professions = Profession::all();

        return view('pages.professions.index', [
            'title' => __('hiko.professions'),
            'professions' => $professions,
        ]);
    }

    public function create()
    {
        if (!tenancy()->initialized) {
            abort(403, __('hiko.tenancy_not_initialized'));
        }

        return view('pages.professions.form', [
            'title' => __('hiko.new_profession'),
            'profession' => new Profession,
            'action' => route('professions.store'),
            'label' => __('hiko.create'),
            'availableCategories' => ProfessionCategory::all(),
        ]);
    }

public function store(ProfessionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->failsDuplicateCheck()) {
            return redirect()->back()
                ->withErrors(['cs' => __('hiko.entity_already_exists')])
                ->withInput();
        }

        $profession = Profession::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
            'profession_category_id' => $validated['profession_category_id'] ?? null,
        ]);

        return redirect()
            ->route('professions.edit', $profession->id)
            ->with('success', __('hiko.saved'));
    }

    public function update(ProfessionRequest $request, Profession $profession): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->failsDuplicateCheck($profession->id)) {
            return redirect()->back()
                ->withErrors(['cs' => __('hiko.entity_already_exists')])
                ->withInput();
        }

        $profession->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
            'profession_category_id' => $validated['profession_category_id'] ?? null,
        ]);

        return redirect()
            ->route('professions.edit', $profession->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(Profession $profession)
    {
        // Load related identities
        $profession->load('identities');

        // Fetch categories based on tenancy status
        $availableCategories = ProfessionCategory::all();

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
