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
        return view('pages.letters.index', [
            'title' => __('hiko.letters'),
            'mainCharacter' => config('hiko.main_character')
                ? Identity::find(config('hiko.main_character'))->value('surname')
                : null,
        ]);
    }

    public function create(): View
    {
        return view('pages.letters.form', [
            'title' => __('hiko.new_letter'),
            'action' => route('letters.store'),
            'label' => __('hiko.create'),
            'letter' => new Letter(),
            'viewData' => $this->prepareViewData(new Letter()),
        ]);
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
        Log::info("Editing letter with ID: {$letter->id}");

        $viewData = $this->prepareViewData($letter);

        return view('pages.letters.form', [
            'title' => __('hiko.letter') . ': ' . $letter->id,
            'method' => 'PUT',
            'action' => route('letters.update', $letter),
            'label' => __('hiko.edit'),
            'letter' => $letter,
            'viewData' => $viewData,
        ] + $viewData);
    }

    public function update(LetterRequest $request, Letter $letter): RedirectResponse
    {
        $letter->update($request->validated());
        Log::info("Updated letter with ID: {$letter->id}");

        $this->attachRelated($request, $letter);

        LetterSaved::dispatch($letter);
        RegenerateNames::dispatch($letter->authors()->get());
        RegenerateNames::dispatch($letter->recipients()->get());

        return redirect()
            ->route('letters.edit', $letter->id)
            ->with('success', __('hiko.saved'));
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

    protected function prepareViewData(Letter $letter): array
    {
        Log::debug("Preparing view data for letter ID: {$letter->id}");

        return [
            'selectedAuthors' => $this->getSelectedMetaFields($letter, 'authors', ['marked']),
            'selectedRecipients' => $this->getSelectedMetaFields($letter, 'recipients', ['marked', 'salutation']),
            'selectedOrigins' => $this->getSelectedMetaFields($letter, 'origins', ['marked']),
            'selectedDestinations' => $this->getSelectedMetaFields($letter, 'destinations', ['marked']),
            'selectedKeywords' => $this->getSelectedMeta($letter, 'Keyword', 'keywords'),
            'selectedMentioned' => $this->getSelectedMeta($letter, 'Identity', 'mentioned'),
            'languages' => Language::pluck('name')->toArray(),
            'selectedLanguages' => $letter->languages ? explode(';', $letter->languages) : [],
        ];
    }

    protected function attachRelated(Request $request, Letter $letter)
    {
        Log::debug("Attaching related data for letter ID: {$letter->id}");

        $letter->keywords()->sync($request->keywords);
        $letter->identities()->detach();
        $letter->places()->detach();

        $letter->identities()->attach($this->prepareAttachmentData($request, 'authors', 'author'));
        $letter->identities()->attach($this->prepareAttachmentData($request, 'recipients', 'recipient', ['salutation']));
        $letter->places()->attach($this->prepareAttachmentData($request, 'origins', 'origin'));
        $letter->places()->attach($this->prepareAttachmentData($request, 'destinations', 'destination'));
    }

    protected function prepareAttachmentData(Request $request, string $fieldKey, string $role, array $pivotFields = []): array
    {
        if (!isset($request->{$fieldKey})) {
            Log::debug("No data found for {$fieldKey}");
            return [];
        }
    
        $items = [];
        foreach ((array) $request->{$fieldKey} as $key => $item) {
            // Extract numeric ID from the input
            $id = isset($item['value']) ? preg_replace('/\D/', '', $item['value']) : null;
    
            if ($id && is_numeric($id)) {
                // Check if the place exists in the tenant's places table
                $placeExists = \DB::table('blekastad__places')->where('id', $id)->exists();
    
                if (!$placeExists) {
                    Log::warning("Place ID {$id} does not exist in blekastad__places table.");
                    continue; // Skip non-existent places
                }
    
                $data = [
                    'position' => $key,
                    'role' => $role,
                    'marked' => $item['marked'] ?? null,
                ];
    
                foreach ($pivotFields as $field) {
                    $data[$field] = $item[$field] ?? null;
                }
    
                $items[$id] = $data;
            } else {
                Log::warning("Invalid data for {$fieldKey}: ", $item);
            }
        }
    
        Log::debug("Processed data for {$fieldKey}: ", $items);
        return $items;
    }    

    protected function getSelectedMetaFields(Letter $letter, string $fieldKey, array $pivotFields): array
    {
        return $letter->{$fieldKey}->map(function ($item) use ($pivotFields) {
            $result = [
                'value' => $item->id,
                'label' => $item->name,
            ];

            foreach ($pivotFields as $field) {
                $result[$field] = $item->pivot->{$field};
            }

            return $result;
        })->toArray();
    }

    protected function getSelectedMeta(Letter $letter, string $model, string $fieldKey): array
    {
        return $letter->{$fieldKey}->map(function ($item) {
            return [
                'value' => $item->id,
                'label' => $item->name,
            ];
        })->toArray();
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
