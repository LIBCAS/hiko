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
 * including storing 'copies' as JSON and attaching pivot data
 * (authors, recipients, keywords, etc.).
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
        // Prepare data for an empty/new Letter
        $viewData = $this->prepareViewData(new Letter);

        return view('pages.letters.form', array_merge([
            'title'  => __('hiko.new_letter'),
            'action' => route('letters.store'),
            'label'  => __('hiko.create'),
            'letter' => new Letter(),  // fresh instance
        ], $viewData));
    }

    /**
     * Store a newly created Letter in the database.
     */
    public function store(LetterRequest $request): RedirectResponse
    {
        // Decide where to redirect after saving
        $redirectRoute = $request->action === 'create' ? 'letters.create' : 'letters.edit';

        // Validate and retrieve data (copies, keywords, etc. are included)
        $validatedData = $request->validated();

        // Remove pivot data (authors, recipients, etc.) from the main letter fields,
        // so we don't try to store them as columns in letters
        unset($validatedData['authors'], $validatedData['recipients'], $validatedData['destinations'], $validatedData['origins']);
        // Do NOT remove 'keywords' so it remains in $validatedData (if we want to handle it directly).
        // But we will handle it in attachRelated() below, anyway.

        // Create the Letter model
        $letter = Letter::create($validatedData);

        // Attach pivot relationships for authors, recipients, places, etc.
        $this->attachRelated($request, $letter);

        // If we have file uploads, attach them as media
        $this->attachMedia($request, $letter);

        // If 'related_resources' is present
        if ($request->has('related_resources')) {
            $letter->related_resources = $request->input('related_resources');
            $letter->save();
        }

        // Dispatch background jobs if needed
        RegenerateNames::dispatch($letter->authors()->get());
        RegenerateNames::dispatch($letter->recipients()->get());

        return redirect()
            ->route($redirectRoute, $letter->id)
            ->with('success', __('hiko.saved'));
    }

    /**
     * Show a single Letter in a read-only view.
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
     * Update an existing Letter record in the database.
     */
    public function update(LetterRequest $request, Letter $letter): RedirectResponse
    {
        // Validate input
        $validatedData = $request->validated();

        // Remove pivot data
        unset($validatedData['authors'], $validatedData['recipients'], $validatedData['destinations'], $validatedData['origins']);

        // Update the main letter fields
        $letter->update($validatedData);
        Log::info("Updated letter with ID: {$letter->id}");

        // Re-attach pivot data (authors, recipients, places, etc.)
        $this->attachRelated($request, $letter);

        // If there are file uploads, attach them
        $this->attachMedia($request, $letter);

        // If related_resources is present, store it
        if ($request->has('related_resources')) {
            $letter->related_resources = $request->input('related_resources');
            $letter->save();
        }

        // Dispatch background jobs if needed
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
        $authors    = $letter->authors()->get();
        $recipients = $letter->recipients()->get();

        // Remove attached media
        foreach ($letter->getMedia() as $media) {
            $media->delete();
        }

        // Delete the letter itself
        $letter->delete();

        // Possibly re-generate names for any authors/recipients
        RegenerateNames::dispatch($authors);
        RegenerateNames::dispatch($recipients);

        return redirect()->route('letters')->with('success', __('hiko.removed'));
    }

    /**
     * Show images related to this Letter.
     */
    public function images(Letter $letter): View
    {
        return view('pages.letters.images', [
            'title'  => __('hiko.letter') . ': ' . $letter->id,
            'letter' => $letter,
        ]);
    }

    /**
     * Show the full text of a Letter, possibly with images.
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
     * Generate a CSV filename for the Palladio tool, based on main character (if any).
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

        // Many-to-many with 'keywords'
        // if present, do a sync
        if ($request->has('keywords')) {
            $letter->keywords()->sync($request->keywords);
        }

        // Detach existing pivot data for identities & places to avoid duplication
        $letter->identities()->detach();
        $letter->places()->detach();

        // authors
        $letter->identities()->attach(
            $this->prepareAttachmentData($request->input('authors'), 'author')
        );

        // recipients
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
     * Each item includes a 'value' => numeric ID, plus optional fields like 'marked'.
     */
    protected function prepareAttachmentData(?array $items, string $role, array $pivotFields = []): array
    {
        if (!$items) {
            return [];
        }

        $results = [];
        foreach ($items as $position => $item) {
            // e.g. "local-5" => "5"
            $id = isset($item['value']) ? preg_replace('/\D/', '', $item['value']) : null;

            if ($id && is_numeric($id)) {
                $data = [
                    'position' => $position,
                    'role'     => $role,
                    'marked'   => $item['marked'] ?? null,
                ];

                // If we have extra pivot fields
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
     * Prepare data for create/edit view (used by create() or edit() methods).
     */
    protected function prepareViewData(Letter $letter): array
    {
        Log::debug("Preparing view data for letter ID: {$letter->id}");

        return [
            // Pre-selected authors, recipients, etc. to pass into Livewire components
            'selectedAuthors'      => $this->getSelectedMetaFields($letter, 'authors', ['marked']),
            'selectedRecipients'   => $this->getSelectedMetaFields($letter, 'recipients', ['marked', 'salutation']),
            'selectedOrigins'      => $this->getSelectedMetaFields($letter, 'origins', ['marked']),
            'selectedDestinations' => $this->getSelectedMetaFields($letter, 'destinations', ['marked']),

            // Pre-selected keywords => array of [ 'value' => id, 'label' => name ]
            'selectedKeywords'     => $this->getSelectedMeta($letter, 'Keyword', 'keywords'),
            // Example: "mentioned" is authors pivot with role= 'mentioned' 
            'selectedMentioned'    => $this->getSelectedMeta($letter, 'Identity', 'mentioned'),

            // Provide languages from a table, or just a static array
            'languages'            => Language::pluck('name')->toArray(),
            'selectedLanguages'    => $letter->languages ? explode(';', $letter->languages) : [],
        ];
    }

    /**
     * Convert a belongsToMany pivot (like authors) into arrays of
     * [ 'value' => <id>, 'label' => <name>, 'marked' => pivot.marked, etc. ].
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
     * Convert a belongsToMany pivot (like 'keywords') to simpler arrays
     * [ 'value' => <id>, 'label' => <name> ].
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
     * Duplicate an existing Letter, copying pivot relationships as well.
     */
    public function duplicate(Request $request, Letter $letter): RedirectResponse
    {
        // Copy main letter fields
        $duplicate = $letter->replicate();
        $duplicate->save();

        $this->duplicateRelatedEntities($letter, $duplicate);

        RegenerateNames::dispatch($duplicate->authors()->get());
        RegenerateNames::dispatch($duplicate->recipients()->get());

        return redirect()->route('letters.edit', $duplicate->id)
                         ->with('success', __('hiko.duplicated'));
    }

    /**
     * Copy pivot data from one letter to another (authors, recipients, places, keywords, etc.).
     */
    protected function duplicateRelatedEntities(Letter $source, Letter $duplicate): void
    {
        // Copy keywords pivot
        $duplicate->keywords()->sync($source->keywords);

        // Clear existing pivot relationships
        $duplicate->identities()->detach();
        $duplicate->places()->detach();

        // Manually attach pivot data
        $this->attachRelatedEntities('authors', 'author', $source, $duplicate);
        $this->attachRelatedEntities('recipients', 'recipient', $source, $duplicate);
        $this->attachRelatedEntities('origins', 'origin', $source, $duplicate);
        $this->attachRelatedEntities('destinations', 'destination', $source, $duplicate);
        $this->attachRelatedEntities('mentioned', 'mentioned', $source, $duplicate);

        // Copy languages if needed
        $duplicate->languages = $source->languages;
        $duplicate->save();
    }

    protected function attachRelatedEntities(string $fieldKey, string $role, Letter $src, Letter $dup): void
    {
        $items = $this->prepareAttachmentDataForEntities($fieldKey, $role, $src);

        if (in_array($role, ['author', 'recipient', 'mentioned'])) {
            $dup->identities()->attach($items);
        } elseif (in_array($role, ['origin', 'destination'])) {
            $dup->places()->attach($items);
        }
    }

    protected function prepareAttachmentDataForEntities(string $fieldKey, string $role, Letter $source): array
    {
        $results = [];
        foreach ($source->{$fieldKey} as $index => $model) {
            $data = [
                'position' => $index,
                'role'     => $role,
                'marked'   => $model->pivot->marked ?? null,
            ];
            if ($role === 'recipient') {
                $data['salutation'] = $model->pivot->salutation ?? null;
            }
            $results[$model->id] = $data;
        }
        return $results;
    }
}
