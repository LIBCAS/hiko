<?php

namespace App\Http\Controllers;

use App\Http\Requests\LetterRequest;
use App\Models\Letter;
use App\Models\Identity;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function index()
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
    public function create()
    {
        return view('pages.letters.form', array_merge([
            'title' => __('hiko.new_letter'),
            'action' => route('letters.store'),
            'label' => __('hiko.create'),
        ], $this->viewData(new Letter)));
    }

    /**
     * Store a newly created Letter in the database.
     */
    public function store(LetterRequest $request): RedirectResponse
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

    /**
     * Show images related to this Letter.
     */
    public function images(Letter $letter)
    {
        return view('pages.letters.images', [
            'title'  => __('hiko.letter') . ': ' . $letter->id,
            'letter' => $letter,
        ]);
    }

    /**
     * Show the full text of a Letter, possibly with images.
     */
    public function text(Letter $letter)
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
    
        $letter->identities()->attach($this->prepareAttachmentData($request->authors, 'author'));
        $letter->identities()->attach($this->prepareAttachmentData($request->recipients, 'recipient', ['salutation']));
        $letter->places()->attach($this->prepareAttachmentData($request->origins, 'origin'));
        $letter->places()->attach($this->prepareAttachmentData($request->destinations, 'destination'));
    
        // Ensure mentioned is an array
        $mentioned = [];
        if (is_array($request->mentioned)) {
            foreach ($request->mentioned as $key => $id) {
                // Extract numeric part from the ID (e.g., 'local-7' -> 7)
                $numericId = preg_replace('/\D/', '', $id);
                if (is_numeric($numericId)) {
                    $mentioned[$numericId] = [
                        'position' => $key,
                        'role' => 'mentioned',
                    ];
                } else {
                    Log::warning("Invalid mentioned ID: {$id}");
                }
            }
        }
    
        $letter->identities()->attach($mentioned);
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
