<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Letter;
use App\Models\Keyword;
use App\Models\Identity;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Exports\LettersExport;
use App\Http\Traits\LetterLabelsTrait;

// TODO: refaktorovat metody pro získání přidružených dat

class LetterController extends Controller
{
    use LetterLabelsTrait;

    protected $rules = [
        'date_year' => ['nullable', 'integer', 'numeric'],
        'date_month' => ['nullable', 'integer', 'numeric'],
        'date_day' => ['nullable', 'integer', 'numeric'],
        'date_marked' => ['nullable', 'string', 'max:255'],
        'date_uncertain' => ['nullable', 'boolean'],
        'date_approximate' => ['nullable', 'boolean'],
        'date_inferred' => ['nullable', 'boolean'],
        'date_is_range' => ['nullable', 'boolean'],
        'range_year' => ['nullable', 'integer', 'numeric'],
        'range_month' => ['nullable', 'integer', 'numeric'],
        'range_day' => ['nullable', 'integer', 'numeric'],
        'date_note' => ['nullable'],
        'author_uncertain' => ['nullable', 'boolean'],
        'author_inferred' => ['nullable', 'boolean'],
        'author_note' => ['nullable'],
        'recipient_uncertain' => ['nullable', 'boolean'],
        'recipient_inferred' => ['nullable', 'boolean'],
        'recipient_note' => ['nullable'],
        'destination_uncertain' => ['nullable', 'boolean'],
        'destination_inferred' => ['nullable', 'boolean'],
        'destination_note' => ['nullable'],
        'origin_uncertain' => ['nullable', 'boolean'],
        'origin_inferred' => ['nullable', 'boolean'],
        'origin_note' => ['nullable'],
        'people_mentioned_note' => ['nullable'],
        'copies' => ['nullable'],
        'related_resources' => ['nullable'],
        'abstract' => ['nullable'],
        'explicit' => ['nullable', 'string', 'max:255'],
        'incipit' => ['nullable', 'string', 'max:255'],
        'copyright' => ['nullable', 'string', 'max:255'],
        'languages' => ['nullable', 'string', 'max:255'],
        'notes_private' => ['nullable'],
        'notes_public' => ['nullable'],
        'status' => ['required', 'string', 'max:255'],
    ];

    protected $copiesFields = [
        'archive',
        'collection',
        'copy',
        'l_number',
        'location_note',
        'manifestation_notes',
        'ms_manifestation',
        'preservation',
        'repository',
        'signature',
        'type',
    ];

    public function index()
    {
        return view('pages.letters.index', [
            'title' => __('hiko.letters'),
        ]);
    }

    public function create()
    {
        $letter = new Letter;

        return view('pages.letters.form', [
            'title' => __('hiko.new_letter'),
            'letter' => $letter,
            'action' => route('letters.store'),
            'label' => __('hiko.create'),
            'selectedAuthors' => $this->getAuthors($letter),
            'selectedRecipients' => $this->getRecipients($letter),
            'selectedOrigins' => $this->getOrigins($letter),
            'selectedDestinations' => $this->getDestinations($letter),
            'selectedKeywords' => $this->getKeywords($letter),
            'selectedMentioned' => $this->getMentioned($letter),
            'selectedCopies' => $this->getCopies($letter),
            'languages' => collect(Language::all())->pluck('name'),
            'labels' => $this->getLabels(),
            'locations' => $this->getLocations(),
        ]);
    }

    public function store(Request $request)
    {
        $request = $this->modifyRequest($request);

        $letter = Letter::create($request->validate($this->rules));

        $this->attachRelated($request, $letter);

        return redirect()
            ->route('letters.edit', $letter->id)
            ->with('success', __('hiko.saved'));
    }

    public function show(Letter $letter)
    {
        $letter->load('identities', 'places', 'keywords');

        return view('pages.letters.show', [
            'title' => $letter->name,
            'letter' => $letter,
        ]);
    }

    public function edit(Letter $letter)
    {
        return view('pages.letters.form', [
            'title' => __('hiko.letter') . ': ' .  $letter->id,
            'letter' => $letter,
            'method' => 'PUT',
            'action' => route('letters.update', $letter),
            'label' => __('hiko.edit'),
            'selectedAuthors' => $this->getAuthors($letter),
            'selectedRecipients' => $this->getRecipients($letter),
            'selectedOrigins' => $this->getOrigins($letter),
            'selectedDestinations' => $this->getDestinations($letter),
            'selectedKeywords' => $this->getKeywords($letter),
            'selectedMentioned' => $this->getMentioned($letter),
            'selectedCopies' => $this->getCopies($letter),
            'languages' => collect(Language::all())->pluck('name'),
            'labels' => $this->getLabels(),
            'locations' => $this->getLocations(),
        ]);
    }

    public function update(Request $request, Letter $letter)
    {
        $request = $this->modifyRequest($request);

        $letter->update($request->validate($this->rules));

        $this->attachRelated($request, $letter);

        return redirect()
            ->route('letters.edit', $letter->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(Letter $letter)
    {
        foreach ($letter->getMedia() as $media) {
            $media->delete();
        }

        $letter->delete();

        return redirect()
            ->route('letters')
            ->with('success', 'hiko.removed');
    }

    public function images(Letter $letter)
    {
        return view('pages.letters.images', [
            'title' => __('hiko.letter') . ': ' .  $letter->id,
            'letter' => $letter,
        ]);
    }

    public function text(Letter $letter)
    {
        return view('pages.letters.text', [
            'title' => __('hiko.full_text') . ' – ' . __('hiko.letter') . ': ' .  $letter->id,
            'letter' => $letter,
            'images' => $letter->getMedia(),
        ]);
    }

    public function export()
    {
        return Excel::download(new LettersExport, 'letters.xlsx');
    }

    protected function modifyRequest(Request $request)
    {
        if (!empty($request->languages)) {
            $request->request->set('languages', implode(';', $request->languages));
        }

        if (!empty($request->related_resources)) {
            $request->request->set('related_resources', json_decode($request->related_resources, true));
        }

        if ($request->copies) {
            $copies = [];

            for ($i = 0; $i < (int) $request->copies; $i++) {
                foreach ($this->copiesFields as $field) {
                    $copies[$i][$field] = $request->{$field}[$i];
                }
            }

            $request->request->set('copies', $copies);
        }

        $request->request->set('abstract', [
            'cs' => $request->abstract_cs,
            'en' => $request->abstract_en,
        ]);

        $booleans = [
            'date_uncertain',
            'date_approximate',
            'date_inferred',
            'date_is_range',
            'author_uncertain',
            'author_inferred',
            'recipient_uncertain',
            'recipient_inferred',
            'destination_uncertain',
            'destination_inferred',
            'origin_uncertain',
            'origin_inferred',
        ];

        foreach ($booleans as $field) {
            $request->request->set($field, isset($request->{$field}) ? 1 : 0);
        }

        return $request;
    }

    protected function attachRelated(Request $request, Letter $letter)
    {
        $letter->keywords()->sync($request->keyword);

        $mentioned = [];
        $authors = [];
        $recipients = [];
        $origins = [];
        $destinations = [];

        $letter->identities()->detach();
        $letter->places()->detach();

        foreach ((array) $request->mentioned as $key => $id) {
            $mentioned[$id] = [
                'position' => $key,
                'role' => 'mentioned',
            ];
        }
        $letter->identities()->attach($mentioned);

        foreach ((array) $request->author as $key => $id) {
            $authors[$id] = [
                'position' => $key,
                'role' => 'author',
                'marked' => $request->author_marked[$key],
            ];
        }
        $letter->identities()->attach($authors);

        foreach ((array) $request->recipient as $key => $id) {
            $recipients[$id] = [
                'position' => $key,
                'role' => 'recipient',
                'marked' => $request->recipient_marked[$key],
                'salutation' => $request->recipient_salutation[$key],

            ];
        }
        $letter->identities()->attach($recipients);

        foreach ((array) $request->origin as $key => $id) {
            $origins[$id] = [
                'position' => $key,
                'role' => 'origin',
                'marked' => $request->origin_marked[$key],
            ];
        }
        $letter->places()->attach($origins);

        foreach ((array) $request->destination as $key => $id) {
            $destinations[$id] = [
                'position' => $key,
                'role' => 'destination',
                'marked' => $request->destination_marked[$key],
            ];
        }
        $letter->places()->attach($destinations);
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

            return $recipients->map(function ($recipient, $index) use ($names, $salutations) {
                return [
                    'id' => $recipient->id,
                    'name' => $recipient->name,
                    'marked' => $names[$index],
                    'salutation' => $salutations[$index],
                ];
            });
        }

        if ($letter->recipients) {
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

    protected function getMentioned(Letter $letter)
    {
        if (request()->old('mentioned')) {
            $ids = request()->old('mentioned');

            $mentions = Identity::whereIn('id', $ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
                ->get();

            return $mentions->map(function ($mentioned) {
                return [
                    'id' => $mentioned->id,
                    'name' => $mentioned->name,
                ];
            });
        }

        if ($letter->mentioned) {
            return $letter->mentioned
                ->map(function ($mentioned) {
                    return [
                        'id' => $mentioned->id,
                        'name' => $mentioned->name,
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

    protected function getKeywords(Letter $letter)
    {
        if (request()->old('keyword')) {
            $ids = request()->old('keyword');

            $keywords = Keyword::whereIn('id', $ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
                ->get();

            return $keywords->map(function ($keyword) {
                return [
                    'id' => $keyword->id,
                    'name' => implode(' | ', array_values($keyword->getTranslations('name'))),
                ];
            });
        }

        if ($letter->keywords) {
            return $letter->keywords
                ->map(function ($keyword) {
                    return [
                        'id' => $keyword->id,
                        'name' => implode(' | ', array_values($keyword->getTranslations('name'))),
                    ];
                })
                ->values()
                ->toArray();
        }
    }

    protected function getCopies(Letter $letter)
    {
        if (request()->old('copies')) {
            $copies = [];

            for ($i = 0; $i < (int) request()->old('copies'); $i++) {
                foreach ($this->copiesFields as $field) {
                    $copies[$i][$field] = request()->old($field)[$i];
                }
            }

            return $copies;
        }

        return empty($letter->copies) ? [] : $letter->copies;
    }
}
