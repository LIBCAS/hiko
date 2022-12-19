<?php

namespace App\Http\Controllers;

use App\Models\Profession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Exports\ProfessionsExport;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfessionController extends Controller
{
    protected array $rules = [
        'cs' => ['max:255', 'required_without:en'],
        'en' => ['max:255', 'required_without:cs'],
    ];

    public function index(): View
    {
        return view('pages.professions.index', [
            'title' => __('hiko.professions'),
        ]);
    }

    public function create(): View
    {
        return view('pages.professions.form', [
            'title' => __('hiko.new_profession'),
            'profession' => new Profession,
            'action' => route('professions.store'),
            'label' => __('hiko.create'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $redirectRoute = $request->action === 'create' ? 'professions.create' : 'professions.edit';

        $validated = $request->validate($this->rules);

        $profession = Profession::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        return redirect()
            ->route($redirectRoute, $profession->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(Profession $profession): View
    {
        return view('pages.professions.form', [
            'title' => __('hiko.profession') . ': ' . $profession->id,
            'profession' => $profession,
            'method' => 'PUT',
            'action' => route('professions.update', $profession),
            'label' => __('hiko.edit'),
        ]);
    }

    public function update(Request $request, Profession $profession): RedirectResponse
    {
        $redirectRoute = $request->action === 'create' ? 'professions.create' : 'professions.edit';

        $validated = $request->validate($this->rules);

        $profession->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        return redirect()
            ->route($redirectRoute, $profession->id)
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
