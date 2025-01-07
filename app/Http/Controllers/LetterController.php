<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\Identity;
use App\Models\Language;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Jobs\RegenerateNames;
use App\Jobs\LetterSaved;
use App\Exports\LettersExport;
use App\Exports\PalladioCharacterExport;
use App\Http\Requests\LetterRequest;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;


class LetterController extends Controller
{
    public function index(): View
    {
        // Retrieve tenant-specific letters (tenant-specific table is handled dynamically by tenancy)
        $letters = Letter::all(); 

        // Fetch the main character based on config
        $mainCharacter = config('hiko.main_character')
            ? Identity::where('id', '=', config('hiko.main_character'))->select('surname')->first()
            : null;
        
        // Return the letters page view with the necessary data
        return view('pages.letters.index', [
            'title' => __('hiko.letters'),
            'mainCharacter' => $mainCharacter ? $mainCharacter->surname : null,
            'letters' => $letters, // Tenant-specific letters
        ]);
    }
    
    public function create(): View
    {
        return view('pages.letters.form', array_merge([
            'title' => __('hiko.new_letter'),
            'action' => route('letters.store'),
            'label' => __('hiko.create'),
        ], $this->viewData(new Letter)));
    }

    public function store(LetterRequest $request): RedirectResponse
    {
        $redirectRoute = $request->action === 'create' ? 'letters.create' : 'letters.edit';
        $letter = Letter::create($request->validated());

        $this->attachRelated($request, $letter);
        RegenerateNames::dispatch($letter->authors()->get());
        RegenerateNames::dispatch($letter->recipients()->get());

        return redirect()->route($redirectRoute, $letter->id)->with('success', __('hiko.saved'));
    }

    public function show(Letter $letter): View
    {
        $letter->load('identities', 'places', 'keywords');

        return view('pages.letters.show', [
            'title' => $letter->name,
            'letter' => $letter,
            'identities' => $letter->identities->groupBy('pivot.role')->toArray(),
            'places' => $letter->places->groupBy('pivot.role')->toArray(),
        ]);
    }

    public function edit(Letter $letter): View
    {
        return view('pages.letters.form', array_merge([
            'title' => __('hiko.letter') . ': ' .  $letter->id,
            'method' => 'PUT',
            'action' => route('letters.update', $letter),
            'label' => __('hiko.edit'),
            'globalKeywords' => \App\Models\GlobalKeyword::all(), // Fetch all global keywords
        ], $this->viewData($letter)));
    }

    public function update(LetterRequest $request, Letter $letter): RedirectResponse
    {
        $redirectRoute = $request->action === 'create' ? 'letters.create' : 'letters.edit';
        $letter->update($request->validated());

        $this->attachRelated($request, $letter);
        LetterSaved::dispatch($letter);
        RegenerateNames::dispatch($letter->authors()->get());
        RegenerateNames::dispatch($letter->recipients()->get());

        return redirect()->route($redirectRoute, $letter->id)->with('success', __('hiko.saved'));
    }

    public function destroy(Letter $letter): RedirectResponse
    {
        $authors = $letter->authors()->get();
        $recipients = $letter->recipients()->get();

        foreach ($letter->getMedia() as $media) {
            $media->delete();
        }

        $letter->delete();

        RegenerateNames::dispatch($authors);
        RegenerateNames::dispatch($recipients);

        return redirect()->route('letters.index')->with('success', __('hiko.removed'));
    }

    public function images(Letter $letter): View
    {
        return view('pages.letters.images', [
            'title' => __('hiko.letter') . ': ' .  $letter->id,
            'letter' => $letter,
        ]);
    }

    public function text(Letter $letter): View
    {
        return view('pages.letters.text', [
            'title' => __('hiko.full_text') . ' – ' . __('hiko.letter') . ': ' .  $letter->id,
            'letter' => $letter,
            'images' => $letter->getMedia(),
        ]);
    }



    public function export(): BinaryFileResponse
    {
        return Excel::download(new LettersExport, 'letters.xlsx');
    }
    
    public function exportPalladioCharacter(Request $request): BinaryFileResponse
    {
        $role = $request->input('role');
    
        // Validate the role input if necessary
        if (!in_array($role, ['author', 'recipient'])) {
            return redirect()->back()->with('error', 'Invalid role specified for export.');
        }
    
        return Excel::download(new PalladioCharacterExport($role), $this->getPalladioFileName($role));
    }
    
    /**
     * Generate a filename for Palladio Character Export.
     *
     * @param string $role
     * @return string
     */
    protected function getPalladioFileName(string $role): string
    {
        $mainCharacter = config('hiko.main_character')
            ? Identity::where('id', config('hiko.main_character'))->select('surname')->first()
            : null;
    
        $surnameSlug = $mainCharacter ? Str::slug($mainCharacter->surname) : 'unknown';
    
        return "palladio-{$surnameSlug}-{$role}.csv";
    }    

    protected function viewData(Letter $letter): array
    {
        return [
            'letter' => $letter,
            'selectedAuthors' => $this->getSelectedMeta($letter, 'Identity', 'authors', ['marked']),
            'selectedRecipients' => $this->getSelectedMeta($letter, 'Identity', 'recipients', ['marked', 'salutation']),
            'selectedOrigins' => $this->getSelectedMeta($letter, 'Place', 'origins', ['marked']),
            'selectedDestinations' => $this->getSelectedMeta($letter, 'Place', 'destinations', ['marked']),
            'selectedKeywords' => $this->getSelectedKeywords($letter),
            'selectedMentioned' => $this->getSelectedMeta($letter, 'Identity', 'mentioned'),
            'languages' => Language::all()->pluck('name'),
            'selectedLanguages' => request()->old('languages', explode(';', $letter->languages ?? '')),
        ];
    }
    
    protected function getSelectedKeywords(Letter $letter): array
    {
        return $letter->keywords->map(fn($keyword) => [
            'value' => $keyword->id,
            'label' => $keyword->getTranslation('name', config('app.locale')),
        ])->toArray();
    }   

    protected function attachRelated(Request $request, Letter $letter)
    {
        // Sync global keyword IDs
        $globalKeywordIds = collect($request->keywords)->filter(function ($keywordId) {
            return \App\Models\GlobalKeyword::find($keywordId); // Check if the global keyword exists
        });
    
        $letter->keywords()->sync($globalKeywordIds);
    
        // Process other relationships
        $letter->identities()->detach();
        $letter->places()->detach();
    
        $letter->identities()->attach($this->prepareAttachmentData($request, 'authors', 'author'));
        $letter->identities()->attach($this->prepareAttachmentData($request, 'recipients', 'recipient', ['salutation']));
        $letter->places()->attach($this->prepareAttachmentData($request, 'origins', 'origin'));
        $letter->places()->attach($this->prepareAttachmentData($request, 'destinations', 'destination'));
    
        // Handle mentioned identities
        $mentioned = [];
        foreach ((array) $request->mentioned as $key => $id) {
            $mentioned[$id] = [
                'position' => $key,
                'role' => 'mentioned',
            ];
        }
        $letter->identities()->attach($mentioned);
    }    
    
    protected function prepareAttachmentData(Request $request, string $fieldKey, string $role, array $pivotFields = []): array
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

    protected function getSelectedMeta(Letter $letter, string $model, string $fieldKey, array $pivotFields = []): array
    {
        // Check for old form data
        if (request()->old($fieldKey)) {
            $ids = request()->old($fieldKey);
            return app("App\Models\\{$model}")
                ->whereIn('id', $ids)
                ->get()
                ->map(function ($item) use ($pivotFields) {
                    $result = [
                        'value' => $item->id,
                        'label' => $item->name,
                    ];
                    foreach ($pivotFields as $field) {
                        $result[$field] = $item->pivot->{$field} ?? null;
                    }
                    return $result;
                })->toArray();
        }
    
        // Fetch related data from the letter model
        return $letter->{$fieldKey}
            ->map(function ($item) use ($pivotFields) {
                $result = [
                    'value' => $item->id,
                    'label' => $item->name,
                ];
                foreach ($pivotFields as $field) {
                    $result[$field] = $item->pivot->{$field} ?? null;
                }
                return $result;
            })
            ->toArray();
    }    

    public function duplicate(Request $request, Letter $letter): RedirectResponse
    {
        $duplicatedLetter = $letter->replicate();
        $duplicatedLetter->save();

        $this->duplicateRelatedEntities($letter, $duplicatedLetter);

        RegenerateNames::dispatch($duplicatedLetter->authors()->get());
        RegenerateNames::dispatch($duplicatedLetter->recipients()->get());

        return redirect()->route('letters.edit', $duplicatedLetter->id)->with('success', __('hiko.duplicated'));
    }

    protected function duplicateRelatedEntities(Letter $sourceLetter, Letter $duplicatedLetter)
    {
        $duplicatedLetter->keywords()->sync($sourceLetter->keywords);
        $duplicatedLetter->identities()->detach();
        $duplicatedLetter->places()->detach();

        $this->attachRelatedEntities('authors', 'author', $sourceLetter, $duplicatedLetter);
        $this->attachRelatedEntities('recipients', 'recipient', $sourceLetter, $duplicatedLetter);
        $this->attachRelatedEntities('origins', 'origin', $sourceLetter, $duplicatedLetter);
        $this->attachRelatedEntities('destinations', 'destination', $sourceLetter, $duplicatedLetter);
        $this->attachRelatedEntities('mentioned', 'mentioned', $sourceLetter, $duplicatedLetter);

        $duplicatedLetter->languages = $sourceLetter->languages;
        $duplicatedLetter->save();
    }

    protected function attachRelatedEntities(string $fieldKey, string $role, Letter $sourceLetter, Letter $duplicatedLetter)
    {
        $items = $this->prepareAttachmentDataForEntities($fieldKey, $role, $sourceLetter);

        foreach ($items as $id => $attributes) {
            if (in_array($role, ['author', 'recipient', 'mentioned'])) {
                $duplicatedLetter->identities()->attach($id, $attributes);
            } elseif (in_array($role, ['origin', 'destination'])) {
                $duplicatedLetter->places()->attach($id, $attributes);
            }
        }
    }

    protected function prepareAttachmentDataForEntities(string $fieldKey, string $role, Letter $sourceLetter): array
    {
        $items = [];

        foreach ($sourceLetter->{$fieldKey} as $key => $item) {
            $result = [
                'position' => $key,
                'role' => $role,
                'marked' => $item->pivot->marked,
            ];

            if ($role === 'recipient') {
                $result['salutation'] = $item->pivot->salutation ?? null;
            }

            $items[$item->id] = $result;
        }

        return $items;
    }
}
