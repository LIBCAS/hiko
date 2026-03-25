<?php

namespace App\Http\Controllers;

use App\Exports\LettersExport;
use App\Exports\PalladioCharacterExport;
use App\Http\Requests\LetterRequest;
use App\Jobs\RegenerateNames;
use App\Models\Letter;
use App\Models\Identity;
use App\Models\Language;
use App\Services\LetterService;
use App\Services\PageLockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * LetterController handles all CRUD operations for the "letters" table,
 * including storing 'copies' as JSON and attaching pivot data
 * (authors, recipients, keywords, etc.).
 */
class LetterController extends Controller
{
    protected LetterService $letterService;

    public function __construct(LetterService $letterService)
    {
        $this->letterService = $letterService;
    }

    /**
     * Display a list of letters in an index view.
     */
    public function index()
    {
        return view('pages.letters.index', [
            'title' => __('hiko.letters'),
            'mainCharacter' => config('hiko.main_character')
                ? optional(Identity::find(config('hiko.main_character')))->value('surname')
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

        // Validated data contains 'copies' array.
        // We must exclude it from the Letter create, as it's not a column anymore.
        $data = $request->validated();
        unset($data['copies']);

        $letter = Letter::create($data);

        $this->letterService->syncManifestations($letter, $request->input('copies', []));

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
        $lock = app(PageLockService::class)->assertOwned([
            'scope' => 'tenant',
            'resource_type' => 'letter_edit',
            'resource_id' => (string) $letter->id,
        ], $request->user());

        if (!$lock['ok']) {
            return redirect()
                ->route('letters')
                ->with('success', __('hiko.page_lock_not_owned'))
                ->with('success_sticky', true);
        }

        $redirectRoute = $request->action === 'create' ? 'letters.create' : 'letters.edit';

        $data = $request->validated();
        unset($data['copies']);

        $letter->update($data);

        $this->letterService->syncManifestations($letter, $request->input('copies', []));

        $this->attachRelated($request, $letter);

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
        $keywords = $request->keywords ?? [];
        $localKeywords = [];
        $globalKeywords = [];
        foreach ($keywords as $kw) {
            $isGlobalKw = str_starts_with($kw, 'global-');
            $kwId = (int) str_replace(['global-', 'local-'], '', $kw);

            if ($isGlobalKw) {
                $globalKeywords[] = $kwId;
            } else {
                $localKeywords[] = $kwId;
            }
        }
        $letter->localKeywords()->sync($localKeywords);
        $letter->globalKeywords()->sync($globalKeywords);

        $letter->identities()->detach();
        $letter->globalIdentities()->detach();
        $letter->localPlaces()->detach();
        $letter->globalPlaces()->detach();

        $this->attachMixedIdentities($letter, $request->authors, 'author');
        $this->attachMixedIdentities($letter, $request->recipients, 'recipient', ['salutation']);
        $this->attachMixedIdentities($letter, $request->mentioned, 'mentioned');

        // Handle origins (local and global places)
        $this->attachPlacesToLetter($letter, $request->origins, 'origin');

        // Handle destinations (local and global places)
        $this->attachPlacesToLetter($letter, $request->destinations, 'destination');
    }

    /**
     * Helper to attach mixed Local/Global identities.
     */
    protected function attachMixedIdentities(Letter $letter, ?array $items, string $role, array $extraFields = [])
    {
        if (empty($items)) {
            return;
        }

        $localSync = [];
        $globalSync = [];

        foreach ($items as $index => $item) {
            // Check if item is just an ID string (legacy/simple) or array structure
            $value = is_array($item) ? ($item['value'] ?? '') : $item;

            // Check prefix
            $isGlobal = str_starts_with($value, 'global-');
            $cleanId = (int) str_replace(['global-', 'local-'], '', $value);

            if ($cleanId > 0) {
                $pivotData = [
                    'role' => $role,
                    'position' => $index,
                    'marked' => $item['marked'] ?? null,
                ];

                foreach ($extraFields as $field) {
                    $pivotData[$field] = $item[$field] ?? null;
                }

                if ($isGlobal) {
                    $globalSync[$cleanId] = $pivotData;
                } else {
                    $localSync[$cleanId] = $pivotData;
                }
            }
        }

        if (!empty($localSync)) {
            $letter->identities()->attach($localSync);
        }

        if (!empty($globalSync)) {
            $letter->globalIdentities()->attach($globalSync);
        }
    }

    protected function duplicateRelatedEntities(Letter $sourceLetter, Letter $duplicatedLetter)
    {
        $duplicatedLetter->localKeywords()->sync($sourceLetter->localKeywords->pluck('id')->toArray());
        $duplicatedLetter->globalKeywords()->sync($sourceLetter->globalKeywords->pluck('id')->toArray());

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
        // Handle origins and destinations (which can be local or global places)
        if ($fieldKey === 'origins' || $fieldKey === 'destinations') {
            $localMethod = $fieldKey; // 'origins' or 'destinations'
            $globalMethod = 'global' . ucfirst($fieldKey); // 'globalOrigins' or 'globalDestinations'

            // Get local places
            $localPlaces = collect($letter->{$localMethod})->map(function ($item) use ($pivotFields) {
                $labelParts = [$item->name];
                if (!empty($item->division)) {
                    $labelParts[] = $item->division;
                }
                if (!empty($item->country)) {
                    $labelParts[] = $item->country;
                }
                $label = implode(', ', array_filter($labelParts));

                $result = [
                    'value' => 'local-' . $item->id,
                    'label' => $label . ' (' . __('hiko.local') . ')',
                ];
                foreach ($pivotFields as $field) {
                    $result[$field] = $item->pivot->{$field};
                }
                return $result;
            });

            // Get global places
            $globalPlaces = collect($letter->{$globalMethod})->map(function ($item) use ($pivotFields) {
                $labelParts = [$item->name];
                if (!empty($item->division)) {
                    $labelParts[] = $item->division;
                }
                if (!empty($item->country)) {
                    $labelParts[] = $item->country;
                }
                $label = implode(', ', array_filter($labelParts));

                $result = [
                    'value' => 'global-' . $item->id,
                    'label' => $label . ' (' . __('hiko.global') . ')',
                ];
                foreach ($pivotFields as $field) {
                    $result[$field] = $item->pivot->{$field};
                }
                return $result;
            });

            return $localPlaces->merge($globalPlaces)->toArray();
        }

        // Handle Identities (Authors, Recipients)
        if (in_array($fieldKey, ['authors', 'recipients'])) {
            $role = rtrim($fieldKey, 's'); // author, recipient

            // Local
            $local = $letter->identities()->where('role', $role)->get()->map(function ($item) use ($pivotFields) {
                $data = [
                    'value' => 'local-' . $item->id,
                    'label' => $item->name . ' (' . __('hiko.local') . ')',
                ];
                foreach ($pivotFields as $field) {
                    $data[$field] = $item->pivot->{$field};
                }
                return $data;
            })->values()->all();

            // Global
            $global = $letter->globalIdentities()->where('role', $role)->get()->map(function ($item) use ($pivotFields) {
                $data = [
                    'value' => 'global-' . $item->id,
                    'label' => $item->name . ' (' . __('hiko.global') . ')',
                ];
                foreach ($pivotFields as $field) {
                    $data[$field] = $item->pivot->{$field};
                }
                return $data;
            })->values()->all();

            return array_values(array_merge($local, $global));
        }

        // Default behavior for other fields
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
        switch ($fieldKey) {
            case 'mentioned':
                $localMentioned = $letter->identities()->where('role', 'mentioned')->get()->map(function ($item) {
                    return [
                        'value' => 'local-' . $item->id,
                        'label' => $item->name . ' (' . __('hiko.local') . ')',
                    ];
                });

                $globalMentioned = $letter->globalIdentities()->where('role', 'mentioned')->get()->map(function ($item) {
                    return [
                        'value' => 'global-' . $item->id,
                        'label' => $item->name . ' (' . __('hiko.global') . ')',
                    ];
                });

                $selectedMeta = collect(array_merge(
                    $localMentioned->values()->all(),
                    $globalMentioned->values()->all()
                ));

                break;
            case 'keywords':
                $globalKws = collect($letter->globalKeywords)->map(function ($kw) {
                    return [
                        'id' => 'global-' . $kw->id,
                        'value' => 'global-' . $kw->id,
                        'label' => $kw->getTranslation('name', config('app.locale')) . ' (' . __('hiko.global') . ')',
                        'type' => __('hiko.global')
                    ];
                });

                $localKws = collect($letter->localKeywords)->map(function ($kw) {
                    return [
                        'id' => 'local-' . $kw->id,
                        'value' => 'local-' . $kw->id,
                        'label' => $kw->getTranslation('name', config('app.locale')) . ' (' . __('hiko.local') . ')',
                        'type' => __('hiko.local')
                    ];
                });

                $selectedMeta = $localKws->merge($globalKws);

                break;
            default:
                $selectedMeta = $letter->{$fieldKey}->map(function ($item) {
                    return [
                        'value' => $item->id,
                        'label' => $item->name,
                    ];
                });
        }

        return is_array($selectedMeta) ? $selectedMeta : $selectedMeta->toArray();
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

    /**
     * Attach places (local or global) to a letter with the specified role.
     *
     * @param Letter $letter
     * @param array|null $places
     * @param string $role
     * @return void
     */
    protected function attachPlacesToLetter(Letter $letter, ?array $places, string $role): void
    {
        if (!$places) {
            return;
        }

        $localPlaces = [];
        $globalPlaces = [];

        foreach ($places as $position => $item) {
            $value = $item['value'] ?? null;
            if (!$value) {
                continue;
            }

            $isGlobal = str_starts_with($value, 'global-');
            $placeId = (int) str_replace(['global-', 'local-'], '', $value);

            if ($placeId) {
                $data = [
                    'position' => $position,
                    'role'     => $role,
                    'marked'   => $item['marked'] ?? null,
                ];

                if ($isGlobal) {
                    $globalPlaces[$placeId] = $data;
                } else {
                    $localPlaces[$placeId] = $data;
                }
            }
        }

        // Attach local and global places
        if (!empty($localPlaces)) {
            $letter->localPlaces()->attach($localPlaces);
        }

        if (!empty($globalPlaces)) {
            $letter->globalPlaces()->attach($globalPlaces);
        }
    }

    public function validation()
    {
        return view('pages.letters.validation', [
            'title' => __('hiko.input_control'),
        ]);
    }
}
