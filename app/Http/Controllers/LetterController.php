<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\Identity;
use App\Models\Language;
use App\Jobs\LetterSaved;
use Illuminate\Http\Request;
use App\Jobs\RegenerateNames;
use App\Exports\LettersExport;
use App\Http\Requests\LetterRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PalladioCharacterExport;

class LetterController extends Controller
{
    public function index()
    {
        return view('pages.letters.index', [
            'title' => __('hiko.letters'),
            'mainCharacter' => config('hiko.main_character')
                ? Identity::where('id', '=', config('hiko.main_character'))->select('surname')->first()->surname
                : null,
        ]);
    }

    public function create()
    {
        return view('pages.letters.form', array_merge([
            'title' => __('hiko.new_letter'),
            'action' => route('letters.store'),
            'label' => __('hiko.create'),
        ], $this->viewData(new Letter)));
    }

    public function store(LetterRequest $request)
    {
        $redirectRoute = $request->action === 'create' ? 'letters.create' : 'letters.edit';

        $letter = Letter::create($request->validated());

        $this->attachRelated($request, $letter);

        RegenerateNames::dispatch($letter->authors()->get());
        RegenerateNames::dispatch($letter->recipients()->get());

        return redirect()
            ->route($redirectRoute, $letter->id)
            ->with('success', __('hiko.saved'));
    }

    public function show(Letter $letter)
    {
        $letter->load('identities', 'places', 'keywords');

        return view('pages.letters.show', [
            'title' => $letter->name,
            'letter' => $letter,
            'identities' => $letter->identities->groupBy('pivot.role')->toArray(),
            'places' => $letter->places->groupBy('pivot.role')->toArray(),
        ]);
    }

    public function edit(Letter $letter)
    {
        return view('pages.letters.form', array_merge([
            'title' => __('hiko.letter') . ': ' .  $letter->id,
            'method' => 'PUT',
            'action' => route('letters.update', $letter),
            'label' => __('hiko.edit'),
        ], $this->viewData($letter)));
    }

    public function update(LetterRequest $request, Letter $letter)
    {
        $redirectRoute = $request->action === 'create' ? 'letters.create' : 'letters.edit';

        $letter->update($request->validated());

        $this->attachRelated($request, $letter);

        LetterSaved::dispatch($letter);
        RegenerateNames::dispatch($letter->authors()->get());
        RegenerateNames::dispatch($letter->recipients()->get());

        return redirect()
            ->route($redirectRoute, $letter->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(Letter $letter)
    {
        $authors = $letter->authors()->get();
        $recipients = $letter->recipients()->get();

        foreach ($letter->getMedia() as $media) {
            $media->delete();
        }

        $letter->delete();

        RegenerateNames::dispatch($authors);
        RegenerateNames::dispatch($recipients);

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

    public function exportPalladioCharacter(Request $request)
    {
        return new PalladioCharacterExport($request->role);
    }

    protected function viewData(Letter $letter)
    {
        return [
            'letter' => $letter,
            'selectedAuthors' => $this->getSelectedMetaFields($letter, 'authors', ['marked']),
            'selectedRecipients' => $this->getSelectedMetaFields($letter, 'recipients', ['marked', 'salutation']),
            'selectedOrigins' => $this->getSelectedMetaFields($letter, 'origins', ['marked']),
            'selectedDestinations' => $this->getSelectedMetaFields($letter, 'destinations', ['marked']),
            'selectedKeywords' => $this->getSelectedMeta($letter, 'Keyword', 'keywords'),
            'selectedMentioned' => $this->getSelectedMeta($letter, 'Identity', 'mentioned'),
            'languages' => collect(Language::all())->pluck('name'),
            'selectedLanguages' => request()->old('languages')
                ? (array) request()->old('languages')
                : explode(';', $letter->languages),
        ];
    }

    protected function attachRelated(Request $request, Letter $letter)
    {
        $letter->keywords()->sync($request->keywords);
        $letter->identities()->detach();
        $letter->places()->detach();
        $letter->identities()->attach($this->prepareAttachmentData($request, 'authors', 'author'));
        $letter->identities()->attach($this->prepareAttachmentData($request, 'recipients', 'recipient', ['salutation']));
        $letter->places()->attach($this->prepareAttachmentData($request, 'origins', 'origin'));
        $letter->places()->attach($this->prepareAttachmentData($request, 'destinations', 'destination'));

        $mentioned = [];
        foreach ((array) $request->mentioned as $key => $id) {
            $mentioned[$id] = [
                'position' => $key,
                'role' => 'mentioned',
            ];
        }

        $letter->identities()->attach($mentioned);
    }

    protected function prepareAttachmentData(Request $request, string $fieldKey, $role, $pivotFields = [])
    {
        if (!isset($request->{$fieldKey})) {
            return [];
        }

        $items = [];

        foreach ((array) $request->{$fieldKey} as $key => $item) {
            if (isset($item['value']) && $item['value']) {
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
            return is_array(request()->old($fieldKey))
                ? request()->old($fieldKey)
                : json_decode(request()->old($fieldKey), true);
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
