<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Letter;
use App\Models\Identity;
use Illuminate\Http\Request;
use App\Exports\LettersExport;

// TODO: refaktorovat metody pro získání přidružených dat

class LetterController extends Controller
{
    protected $rules = [
        'name' => 'required',
    ];

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
            'selectedRecipients' => $this->getRecipients($letter),
            'selectedOrigins' => $this->getOrigins($letter),
            'selectedDestinations' => $this->getDestinations($letter),
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
            'selectedRecipients' => $this->getRecipients($letter),
            'selectedOrigins' => $this->getOrigins($letter),
            'selectedDestinations' => $this->getDestinations($letter),
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

    protected function getRecipients(Letter $letter)
    {
        if (request()->old('recipient')) {
            $ids = request()->old('recipient');
            $names = request()->old('recipient_marked');
            $salutations = request()->old('recipient_salutation');

            $recipients = Identity::whereIn('id', $ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
                ->get();

            return $recipients->map(function ($author, $index) use ($names, $salutations) {
                return [
                    'id' => $author->id,
                    'name' => $author->name,
                    'marked' => $names[$index],
                    'salutation' => $salutations[$index],
                ];
            });
        }

        if ($letter->recipients()) {
            return $letter->recipients
                ->map(function ($recipient) {
                    return [
                        'id' => $recipient->id,
                        'name' => $recipient->name,
                        'marked' => $recipient->pivot->marked,
                        'salutation' => $recipient->pivot->salutation,
                    ];
                })
                ->values()
                ->toArray();
        }
    }

    protected function getOrigins(Letter $letter)
    {
        if (request()->old('origin')) {
            $ids = request()->old('origin');
            $names = request()->old('origin_marked');

            $origins = Place::whereIn('id', $ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
                ->get();

            return $origins->map(function ($origin, $index) use ($names) {
                return [
                    'id' => $origin->id,
                    'name' => $origin->name,
                    'marked' => $names[$index],
                ];
            });
        }

        if ($letter->origins) {
            return $letter->origins
                ->map(function ($origin) {
                    return [
                        'id' => $origin->id,
                        'name' => $origin->name,
                        'marked' => $origin->pivot->marked,
                    ];
                })
                ->values()
                ->toArray();
        }
    }

    protected function getDestinations(Letter $letter)
    {
        if (request()->old('destination')) {
            $ids = request()->old('destination');
            $names = request()->old('destination_marked');

            $destinations = Place::whereIn('id', $ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
                ->get();

            return $destinations->map(function ($destination, $index) use ($names) {
                return [
                    'id' => $destination->id,
                    'name' => $destination->name,
                    'marked' => $names[$index],
                ];
            });
        }

        if ($letter->destinations) {
            return $letter->destinations
                ->map(function ($destination) {
                    return [
                        'id' => $destination->id,
                        'name' => $destination->name,
                        'marked' => $destination->pivot->marked,
                    ];
                })
                ->values()
                ->toArray();
        }
    }
}
