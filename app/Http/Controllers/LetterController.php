<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use Illuminate\Http\Request;
use App\Exports\LettersExport;

class LetterController extends Controller
{
    public function index()
    {
        return view('pages.letters.index', [
            'title' => __('Dopisy'),
        ]);
    }

    public function create()
    {
        return view('pages.letters.form', [
            'title' => __('Nový dopis'),
            'letter' => new Letter(),
            'action' => route('letters.store'),
            'label' => __('Vytvořit'),
        ]);
    }

    public function store(Request $request)
    {
    }

    public function show(Letter $letter)
    {
    }

    public function edit(Letter $letter)
    {
        return view('pages.letters.form', [
            'title' => __('Dopis: '),
            'letter' => $letter,
            'method' => 'PUT',
            'action' => route('letters.update', $letter),
            'label' => __('Upravit'),
        ]);
    }

    public function update(Request $request, Letter $letter)
    {
    }

    public function destroy(Letter $letter)
    {
    }

    public function export()
    {
        return Excel::download(new LettersExport, 'letters.xlsx');
    }
}
