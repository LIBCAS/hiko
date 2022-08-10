<?php

namespace App\Http\Controllers;

use App\Models\Profession;
use Illuminate\Http\Request;
use App\Exports\ProfessionsExport;
use Maatwebsite\Excel\Facades\Excel;

class ProfessionController extends Controller
{
    protected $rules = [
        'cs' => ['max:255', 'required_without:en'],
        'en' => ['max:255', 'required_without:cs'],
    ];

    public function index()
    {
        return view('pages.professions.index', [
            'title' => __('hiko.professions'),
        ]);
    }

    public function create()
    {
        return view('pages.professions.form', [
            'title' => __('hiko.new_profession'),
            'profession' => new Profession,
            'action' => route('professions.store'),
            'label' => __('hiko.create'),
        ]);
    }

    public function store(Request $request)
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

    public function edit(Profession $profession)
    {
        return view('pages.professions.form', [
            'title' => __('hiko.profession') . ': ' . $profession->id,
            'profession' => $profession,
            'method' => 'PUT',
            'action' => route('professions.update', $profession),
            'label' => __('hiko.edit'),
        ]);
    }

    public function update(Request $request, Profession $profession)
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

    public function destroy(Profession $profession)
    {
        $profession->delete();

        return redirect()
            ->route('professions')
            ->with('success', __('hiko.removed'));
    }

    public function export()
    {
        return Excel::download(new ProfessionsExport, 'professions.xlsx');
    }
}
