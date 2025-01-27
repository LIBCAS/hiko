<?php

namespace App\Http\Controllers;

use App\Http\Requests\LetterRequest;
use App\Models\Letter;
use App\Models\Identity;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Jobs\RegenerateNames;
use App\Jobs\LetterSaved;
use App\Exports\LettersExport;
use App\Exports\PalladioCharacterExport;

/**
 * LetterController handles all CRUD operations for the "letters" table,
 * including storing 'copies' as JSON (via model casting) and attaching
 * pivot data (authors, recipients, origins, destinations, etc.).
 */
class LetterController extends Controller
{
    /**
     * Display a list of letters in an index view.
     */
    public function index(): View
    {
        return view('pages.letters.index', [
            'title' => __('hiko.letters'),
            'mainCharacter' => config('hiko.main_character')
                ? Identity::find(config('hiko.main_character'))->value('surname')
                : null,
        ]);
    }

    /**
     * Show the form to create a new Letter record.
     */
    public function create(): View
    {
        // Prepare any data needed for the form
        $viewData = $this->prepareViewData(new Letter);

        return view('pages.letters.form', array_merge([
            'title'  => __('hiko.new_letter'),
            'action' => route('letters.store'),
            'label'  => __('hiko.create'),
            'letter' => new Letter(),  // a fresh Letter model
        ], $viewData));
    }

    /**
     * Store a newly created Letter in the database.
     * 'copies' is automatically cast to JSON by the Letter model.
     */
    public function store(LetterRequest $request): RedirectResponse
    {
        // Decide which route to redirect to afterwards
        $redirectRoute = $request->action === 'create' ? 'letters.create' : 'letters.edit';

        // Validate incoming data. 'copies' and others are included here.
        $validatedData = $request->validated();

        // Remove pivot fields (authors, recipients, etc.) that belong to pivot tables,
        // so we do not store them in the letters table directly.
        unset($validatedData['authors'], $validatedData['recipients'], $validatedData['destinations'], $validatedData['origins']);

        // Create a new Letter. Because $casts['copies'] = 'array' in the model,
        // any array assigned to "copies" will be serialized to JSON automatically.
        $letter = Letter::create($validatedData);

        // Attach pivot relationships (authors, recipients, places, etc.)
        $this->attachRelated($request, $letter);

        // If file uploads exist, attach them as media if needed
        $this->attachMedia($request, $letter);

        // If there is an array of related_resources, store it
        if ($request->has('related_resources')) {
            $letter->related_resources = $request->input('related_resources');
            $letter->save();
        }

        // Dispatch jobs to regenerate name fields if necessary
        RegenerateNames::dispatch($letter->authors()->get());
        RegenerateNames::dispatch($letter->recipients()->get());

        return redirect()
            ->route($redirectRoute, $letter->id)
            ->with('success', __('hiko.saved'));
    }

    /**
     * Show a Letter in a read-only view.
     */
    public function show(Letter $letter): View
    {
        // Eager-load pivot relationships
        $letter->load('identities', 'places', 'keywords');

        return view('pages.letters.show', [
            'title'      => $letter->name,
            'letter'     => $letter,
            'identities' => $letter->identities->groupBy('pivot.role')->toArray(),
            'places'     => $letter->places->groupBy('pivot.role')->toArray(),
        ]);
    }

    /**
     * Show the form to edit an existing Letter.
     */
    public function edit(Letter $letter): View
    {
        Log::info("Editing letter with ID: {$letter->id}");

        $viewData = $this->prepareViewData($letter);

        return view('pages.letters.form', array_merge([
            'title'  => __('hiko.letter') . ': ' . $letter->id,
            'method' => 'PUT',
            'action' => route('letters.update', $letter),
            'label'  => __('hiko.edit'),
            'letter' => $letter,
        ], $viewData));
    }

    /**
     * Update an existing Letter in the database.
     */
    public function update(LetterRequest $request, Letter $letter): RedirectResponse
    {
        // Validate input
        $validatedData = $request->validated();

        // Remove pivot data, as they are stored separately
        unset($validatedData['authors'], $validatedData['recipients'], $validatedData['destinations'], $validatedData['origins']);

        // Update the Letter with non-pivot fields
        $letter->update($validatedData);
        Log::info("Updated letter with ID: {$letter->id}");

        // Re-attach pivot data (authors, recipients, etc.)
        $this->attachRelated($request, $letter);

        // Optionally attach media uploads
        $this->attachMedia($request, $letter);

        // If related resources are updated, store them
        if ($request->has('related_resources')) {
            $letter->related_resources = $request->input('related_resources');
            $letter->save();
        }

        // Dispatch any relevant background jobs
        LetterSaved::dispatch($letter);
        RegenerateNames::dispatch($letter->authors()->get());
        RegenerateNames::dispatch($letter->recipients()->get());

        return redirect()
            ->route('letters.edit', $letter->id)
            ->with('success', __('hiko.saved'));
    }

    /**
     * Remove an existing Letter from the database.
     */
    public function destroy(Letter $letter): RedirectResponse
    {
        // Gather authors and recipients for regeneration
        $authors    = $letter->authors()->get();
        $recipients = $letter->recipients()->get();

        // Remove attached media
        foreach ($letter->getMedia() as $media) {
            $media->delete();
        }

        // Delete the Letter itself
        $letter->delete();

        // Possibly re-generate names that were associated with it
        RegenerateNames::dispatch($authors);
        RegenerateNames::dispatch($recipients);

        return redirect()->route('letters')->with('success', __('hiko.removed'));
    }

    /**
     * Show the images related to a given Letter.
     */
    public function images(Letter $letter): View
    {
        return view('pages.letters.images', [
            'title'  => __('hiko.letter') . ': ' . $letter->id,
            'letter' => $letter,
        ]);
    }

    /**
     * Show the full text of a Letter, optionally including images.
     */
    public function text(Letter $letter): View
    {
        return view('pages.letters.text', [
            'title'  => __('hiko.full_text') . ' – ' . __('hiko.letter') . ': ' .  $letter->id,
            'letter' => $letter,
            'images' => $letter->getMedia(),
        ]);
    }

    /**
     * Example of exporting Letters to an Excel file.
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new LettersExport, 'letters.xlsx');
    }

    /**
     * Export data about authors or recipients for the Palladio tool.
     */
    public function exportPalladioCharacter(Request $request): BinaryFileResponse
    {
        $role = $request->input('role');
        if (!in_array($role, ['author', 'recipient'])) {
            return redirect()->back()->with('error', 'Invalid role specified for export.');
        }

        return Excel::download(new PalladioCharacterExport($role), $this->getPalladioFileName($role));
    }

    /**
     * Generate a CSV filename for the Palladio tool, based on the role and main character.
     */
    protected function getPalladioFileName(string $role): string
    {
        $mainCharacter = config('hiko.main_character')
            ? Identity::where('id', config('hiko.main_character'))->select('surname')->first()
            : null;

        $surnameSlug = $mainCharacter ? Str::slug($mainCharacter->surname) : 'unknown';
        return "palladio-{$surnameSlug}-{$role}.csv";
    }

    /**
     * Attach pivot data for authors, recipients, origins, destinations, etc.
     */
    protected function attachRelated(Request $request, Letter $letter): void
    {
        Log::debug("Attaching related data for letter ID: {$letter->id}");

        // Example of many-to-many with 'keywords'
        if ($request->has('keywords')) {
            $letter->keywords()->sync($request->keywords);
        }

        // Detach existing pivot data to avoid duplication
        $letter->identities()->detach();
        $letter->places()->detach();

        // authors
        $letter->identities()->attach(
            $this->prepareAttachmentData($request->input('authors'), 'author')
        );

        // recipients (with an optional 'salutation')
        $letter->identities()->attach(
            $this->prepareAttachmentData($request->input('recipients'), 'recipient', ['salutation'])
        );

        // origins
        $letter->places()->attach(
            $this->prepareAttachmentData($request->input('origins'), 'origin')
        );

        // destinations
        $letter->places()->attach(
            $this->prepareAttachmentData($request->input('destinations'), 'destination')
        );
    }

    /**
     * Convert an array of items from the request into pivot data for attach().
     * Each item has at least a 'value' => ID, possibly plus fields like 'marked', etc.
     */
    protected function prepareAttachmentData(?array $items, string $role, array $pivotFields = []): array
    {
        if (!$items) {
            return [];
        }

        $results = [];
        foreach ($items as $position => $item) {
            // For instance, "local-5" => "5"
            $id = isset($item['value']) ? preg_replace('/\D/', '', $item['value']) : null;

            if ($id && is_numeric($id)) {
                $data = [
                    'position' => $position,
                    'role'     => $role,
                    'marked'   => $item['marked'] ?? null,
                ];

                // If we have extra pivot fields, copy them
                foreach ($pivotFields as $field) {
                    $data[$field] = $item[$field] ?? null;
                }

                $results[$id] = $data;
            } else {
                Log::warning("Invalid pivot data for role '{$role}' at position {$position}.", $item);
            }
        }

        return $results;
    }

    /**
     * Optionally attach uploaded files as media (spatie/laravel-medialibrary).
     */
    protected function attachMedia(Request $request, Letter $letter): void
    {
        if (!$request->has('uploadedFiles')) {
            return;
        }

        try {
            $filePaths = json_decode($request->input('uploadedFiles'), true);
            if (empty($filePaths)) {
                return;
            }

            foreach ($filePaths as $filePath) {
                $fileName = pathinfo($filePath, PATHINFO_BASENAME);
                $letter->addMedia($filePath)
                       ->usingFileName($fileName)
                       ->toMediaCollection();
            }
        } catch (\Exception $e) {
            Log::error("Error attaching media to letter ID {$letter->id}: {$e->getMessage()}");
        }
    }

    /**
     * Prepare data for the create/edit view, such as selected authors, places, etc.
     */
    protected function prepareViewData(Letter $letter): array
    {
        Log::debug("Preparing view data for letter ID: {$letter->id}");

        return [
            // For example, grouping authors by 'marked'
            'selectedAuthors'      => $this->getSelectedMetaFields($letter, 'authors', ['marked']),
            'selectedRecipients'   => $this->getSelectedMetaFields($letter, 'recipients', ['marked', 'salutation']),
            'selectedOrigins'      => $this->getSelectedMetaFields($letter, 'origins', ['marked']),
            'selectedDestinations' => $this->getSelectedMetaFields($letter, 'destinations', ['marked']),

            'selectedKeywords'   => $this->getSelectedMeta($letter, 'Keyword', 'keywords'),
            'selectedMentioned'  => $this->getSelectedMeta($letter, 'Identity', 'mentioned'),

            // Provide a list of languages from the DB. If letter->languages is "Czech;Latin",
            // we split it into an array for multi-select usage in the form.
            'languages'         => Language::pluck('name')->toArray(),
            'selectedLanguages' => $letter->languages ? explode(';', $letter->languages) : [],
        ];
    }

    /**
     * Convert a belongsToMany pivot (like authors) into an array of
     * ['value' => <id>, 'label' => <name>, 'marked' => ..., etc.].
     */
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

    /**
     * Convert a belongsToMany pivot (like 'keywords') into simple arrays
     * of ['value' => <id>, 'label' => <name>].
     */
    protected function getSelectedMeta(Letter $letter, string $model, string $fieldKey): array
    {
        return $letter->{$fieldKey}->map(function ($item) {
            return [
                'value' => $item->id,
                'label' => $item->name,
            ];
        })->toArray();
    }

    /**
     * Duplicate an existing Letter record, including pivot data (authors, recipients, etc.).
     */
    public function duplicate(Request $request, Letter $letter): RedirectResponse
    {
        // Create a copy of the main letter fields
        $duplicate = $letter->replicate();
        $duplicate->save();

        $this->duplicateRelatedEntities($letter, $duplicate);

        RegenerateNames::dispatch($duplicate->authors()->get());
        RegenerateNames::dispatch($duplicate->recipients()->get());

        return redirect()->route('letters.edit', $duplicate->id)
                         ->with('success', __('hiko.duplicated'));
    }

    /**
     * Copy pivot relationships (identities, places, etc.) from one Letter to another.
     */
    protected function duplicateRelatedEntities(Letter $source, Letter $duplicate): void
    {
        // Copy any many-to-many pivot data for keywords
        $duplicate->keywords()->sync($source->keywords);

        // Clear existing pivot relationships on the new record
        $duplicate->identities()->detach();
        $duplicate->places()->detach();

        // Manually attach pivot data from the source letter
        $this->attachRelatedEntities('authors', 'author', $source, $duplicate);
        $this->attachRelatedEntities('recipients', 'recipient', $source, $duplicate);
        $this->attachRelatedEntities('origins', 'origin', $source, $duplicate);
        $this->attachRelatedEntities('destinations', 'destination', $source, $duplicate);
        $this->attachRelatedEntities('mentioned', 'mentioned', $source, $duplicate);

        // Copy any other relevant fields, like languages
        $duplicate->languages = $source->languages;
        $duplicate->save();
    }

    /**
     * Helper to attach pivot data for a specific role (author, recipient, origin, destination).
     */
    protected function attachRelatedEntities(string $fieldKey, string $role, Letter $src, Letter $dup): void
    {
        $items = $this->prepareAttachmentDataForEntities($fieldKey, $role, $src);

        // If it's identity-based data
        if (in_array($role, ['author', 'recipient', 'mentioned'])) {
            $dup->identities()->attach($items);
        }
        // If it's place-based data
        elseif (in_array($role, ['origin', 'destination'])) {
            $dup->places()->attach($items);
        }
    }

    /**
     * Convert each pivot record from the source letter into attachable array data.
     */
    protected function prepareAttachmentDataForEntities(string $fieldKey, string $role, Letter $source): array
    {
        $results = [];
        foreach ($source->{$fieldKey} as $index => $model) {
            $data = [
                'position' => $index,
                'role'     => $role,
                'marked'   => $model->pivot->marked,
            ];
            // For 'recipient' role, add 'salutation'
            if ($role === 'recipient') {
                $data['salutation'] = $model->pivot->salutation ?? null;
            }
            $results[$model->id] = $data;
        }
        return $results;
    }
}
