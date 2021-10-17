<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use Illuminate\Http\Request;
use App\Exports\LettersExport;
use App\Models\Identity;

class LetterController extends Controller
{
    protected $rules = [];

    public function index()
    {
        return view('pages.letters.index', [
            'title' => __('Dopisy'),
        ]);
    }

    public function create()
    {
        $letter = new Letter();

        return view('pages.letters.form', [
            'title' => __('Nový dopis'),
            'letter' => $letter,
            'action' => route('letters.store'),
            'label' => __('Vytvořit'),
            'selectedAuthors' => $this->getAuthors($letter),

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
            'selectedAuthors' => $this->getAuthors($letter),

        ]);
    }

    public function update(Request $request, Letter $letter)
    {
        $request->validate($this->rules);
    }

    public function destroy(Letter $letter)
    {
    }

    public function export()
    {
        return Excel::download(new LettersExport, 'letters.xlsx');
    }

    protected function getAuthors(Letter $letter)
    {
        if (request()->old('author')) {
            $ids = request()->old('author');
            $names = request()->old('author_marked');

            $authors = Identity::whereIn('id', $ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
                ->get();

            return $authors->map(function ($author, $index) use ($names) {
                return [
                    'id' => $author->id,
                    'name' => $author->name,
                    'marked' => $names[$index],
                ];
            });
        }

        if ($letter->authors) {
            return $letter->authors
                ->map(function ($author) {
                    return [
                        'id' => $author->id,
                        'name' => $author->name,
                        'marked' => $author->pivot->marked,
                    ];
                })
                ->values()
                ->toArray();
        }
    }
}
