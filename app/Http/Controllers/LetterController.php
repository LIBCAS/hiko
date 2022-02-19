<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Exports\LettersExport;

class LetterController extends Controller
{
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
            'selectedAuthors' => $this->getSelectedMetaFields($letter, 'authors', ['marked']),
            'selectedRecipients' => $this->getSelectedMeta($letter, 'Identity', 'recipients', ['marked', 'salutation']),
            'selectedOrigins' => $this->getSelectedMeta($letter, 'Place', 'origins', ['marked']),
            'selectedDestinations' => $this->getSelectedMeta($letter, 'Place', 'destinations', ['marked']),
            'selectedKeywords' => $this->getSelectedMeta($letter, 'Keyword', 'keywords'),
            'selectedMentioned' => $this->getSelectedMeta($letter, 'Identity', 'mentioned'),
            'languages' => collect(Language::all())->pluck('name'),
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
            'selectedAuthors' => $this->getSelectedMetaFields($letter, 'authors', ['marked']),
            'selectedRecipients' => $this->getSelectedMeta($letter, 'Identity', 'recipients', ['marked', 'salutation']),
            'selectedOrigins' => $this->getSelectedMeta($letter, 'Place', 'origins', ['marked']),
            'selectedDestinations' => $this->getSelectedMeta($letter, 'Place', 'destinations', ['marked']),
            'selectedKeywords' => $this->getSelectedMeta($letter, 'Keyword', 'keywords'),
            'selectedMentioned' => $this->getSelectedMeta($letter, 'Identity', 'mentioned'),
            'languages' => collect(Language::all())->pluck('name'),
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
            $request->request->set('copies', json_decode($request->copies, true));
        }

        if ($request->authors) {
            $request->request->set('authors', json_decode($request->authors, true));
        }

        $request->request->set('abstract', [
            'cs' => $request->abstract_cs,
            'en' => $request->abstract_en,
        ]);

        foreach ($this->rules as $key => $fieldRules) {
            if (in_array('boolean', $fieldRules)) {
                $request->request->set($key, isset($request->{$key}) ? 1 : 0);
            }
        }

        return $request;
    }

    protected function attachRelated(Request $request, Letter $letter)
    {
        $letter->keywords()->sync($request->keywords);

        $mentioned = [];
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

        $letter->identities()->attach($this->prepareAttachmentData($request, 'authors', 'author'));

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

    protected function prepareAttachmentData(Request $request, string $fieldKey, $role, $pivotFields = [])
    {
        $items = [];

        foreach ($request->{$fieldKey} as $key => $item) {
            if ($item['value']) {
                $result = [
                    'position' => $key,
                    'role' => $role,
                    'marked' => $item['marked'],
                ];

                foreach ($pivotFields as $field) {
                    $result[$field] = $item[$field];
                }

                $items[$item['value']] = $result;
            }
        }

        return $items;
    }

    protected function getSelectedMetaFields(Letter $letter, string $fieldKey, $pivotFields)
    {
        if (request()->old($fieldKey)) {
            return request()->old($fieldKey);
        }

        return $letter->{$fieldKey}
            ->map(function ($item) use ($pivotFields) {
                $result = [
                    'value' => $item->id,
                    'label' => $item->name,
                ];

                foreach ($pivotFields as $field) {
                    $result[$field] = $item->pivot->{$field};
                }

                return $result;
            })
            ->toArray();
    }

    protected function getSelectedMeta(Letter $letter, $model, string $fieldKey)
    {
        if (!request()->old($fieldKey) && !$letter->{$fieldKey}) {
            return [];
        }

        $items = request()->old($fieldKey)
            ? app('App\Models\\' . $model)::whereIn('id', request()->old($fieldKey))
            ->orderByRaw('FIELD(id, ' . implode(',', request()->old($fieldKey)) . ')')
            ->get()
            : $letter->{$fieldKey};

        return $items
            ->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => is_array($item->name)
                        ? $item->getTranslation('name', config('hiko.metadata_default_locale'))
                        : $item->name,
                ];
            })
            ->toArray();
    }
}
