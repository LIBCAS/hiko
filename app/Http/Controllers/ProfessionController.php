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
            'title' => __('Profese'),
        ]);
    }

    public function create()
    {
        return view('pages.professions.form', [
            'title' => __('Nová profese'),
            'profession' => new Profession(),
            'action' => route('professions.store'),
            'label' => __('Vytvořit'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules);

        $profession = Profession::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        return redirect()->route('professions.edit', $profession->id)->with('success', __('Uloženo.'));
    }

    public function edit(Profession $profession)
    {
        return view('pages.professions.form', [
            'title' => __('Profese: '),
            'profession' => $profession,
            'method' => 'PUT',
            'action' => route('professions.update', $profession),
            'label' => __('Upravit'),
        ]);
    }

    public function update(Request $request, Profession $profession)
    {
        $validated = $request->validate($this->rules);

        $profession->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        return redirect()->route('professions.edit', $profession->id)->with('success', __('Uloženo.'));
    }

    public function destroy(Profession $profession)
    {
        $profession->delete();

        return redirect()->route('professions')->with('success', __('Odstraněno'));
    }

    public function export()
    {
        return Excel::download(new ProfessionsExport, 'professions.xlsx');
    }
}
