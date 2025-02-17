<?php

namespace App\Livewire;

use App\Models\Letter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

class LettersTable extends Component
{
    use WithPagination;

    protected $middleware = [
        InitializeTenancyByRequestData::class
    ];

    public array $filters = [];
    public array $sorting = [
        'order' => 'updated_at',
        'direction' => 'desc',
    ];

    protected $listeners = ['filtersChanged', 'sortingChanged', 'removeFilter', 'resetLettersTablePage'];

    public function mount()
    {
        $this->filters = session()->get('lettersTableFilters', []);
        $this->sorting = session()->get('lettersTableSorting', [
            'order' => 'updated_at',
            'direction' => 'desc',
        ]);
    }

    public function filtersChanged(array $filters)
    {
        $this->filters = $filters;
        session()->put('lettersTableFilters', $this->filters);
        $this->resetPage();
    }

    public function sortingChanged(array $sorting)
    {
        $this->sorting = $sorting;
        session()->put('lettersTableSorting', $this->sorting);
        $this->resetPage();
    }

    public function removeFilter(string $filterKey)
    {
        unset($this->filters[$filterKey]);
        session()->put('lettersTableFilters', $this->filters);
        $this->resetPage();
    }

    public function resetLettersTablePage()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->filters = [];
        session()->forget('lettersTableFilters');
        $this->resetPage();
    }

    public function render()
    {
        $letters = $this->findLetters();
        return view('livewire.letters-table', [
            'tableData' => $this->formatTableData($letters),
            'pagination' => $letters,
        ]);
    }

    protected function findLetters(): LengthAwarePaginator
    {
        $tenantPrefix = tenancy()->initialized ? tenancy()->tenant->table_prefix . '__' : '';
    
        $lettersTable = "{$tenantPrefix}letters";
        $identityLetterTable = "{$tenantPrefix}identity_letter";
        $identitiesTable = "{$tenantPrefix}identities";
        $letterPlaceTable = "{$tenantPrefix}letter_place";
        $placesTable = "{$tenantPrefix}places";
        $keywordLetterTable = "{$tenantPrefix}keyword_letter";
        $keywordsTable = "{$tenantPrefix}keywords";
        $mediaTable = "{$tenantPrefix}media";
    
        // Check if media table exists
        $mediaTableExists = \DB::connection('tenant')->getSchemaBuilder()->hasTable($mediaTable);
    
        // Base Query
        $query = Letter::from("{$lettersTable} as letters")
            ->select([
                'letters.id', 'letters.uuid', 'letters.history', 'letters.date_computed',
                'letters.updated_at', 'letters.status', 'letters.approval',
                \DB::raw("MIN(authors.name) as author_name"),
                \DB::raw("MIN(recipients.name) as recipient_name"),
                \DB::raw("MIN(origins.name) as origin_name"),
                \DB::raw("MIN(destinations.name) as destination_name"),
                \DB::raw("MIN(keywords.name) as keyword_name"),
            ])
            ->leftJoin("{$identityLetterTable} as authors_pivot", function ($join) {
                $join->on('letters.id', '=', 'authors_pivot.letter_id')
                    ->where('authors_pivot.role', '=', 'author');
            })
            ->leftJoin("{$identitiesTable} as authors", 'authors_pivot.identity_id', '=', 'authors.id')
    
            ->leftJoin("{$identityLetterTable} as recipients_pivot", function ($join) {
                $join->on('letters.id', '=', 'recipients_pivot.letter_id')
                    ->where('recipients_pivot.role', '=', 'recipient');
            })
            ->leftJoin("{$identitiesTable} as recipients", 'recipients_pivot.identity_id', '=', 'recipients.id')
    
            ->leftJoin("{$letterPlaceTable} as origins_pivot", function ($join) {
                $join->on('letters.id', '=', 'origins_pivot.letter_id')
                    ->where('origins_pivot.role', '=', 'origin');
            })
            ->leftJoin("{$placesTable} as origins", 'origins_pivot.place_id', '=', 'origins.id')
    
            ->leftJoin("{$letterPlaceTable} as destinations_pivot", function ($join) {
                $join->on('letters.id', '=', 'destinations_pivot.letter_id')
                    ->where('destinations_pivot.role', '=', 'destination');
            })
            ->leftJoin("{$placesTable} as destinations", 'destinations_pivot.place_id', '=', 'destinations.id')
    
            ->leftJoin("{$keywordLetterTable} as keyword_pivot", 'letters.id', '=', 'keyword_pivot.letter_id')
            ->leftJoin("{$keywordsTable} as keywords", 'keyword_pivot.keyword_id', '=', 'keywords.id');
    
        // Include media count only if media table exists
        if ($mediaTableExists) {
            $query->addSelect([
                \DB::raw("(SELECT COUNT(*) FROM {$mediaTable} as media WHERE media.model_id = letters.id AND media.model_type = '" . addslashes(Letter::class) . "') AS media_count")
            ]);
        }
    
        // 🔹 **Filtering**
        if (!empty($this->filters['id'])) {
            $query->where('letters.id', 'like', '%' . $this->filters['id'] . '%');
        }
        
        if (!empty($this->filters['date_from'])) {
            $query->where('letters.date_computed', '>=', $this->filters['date_from']);
        }
    
        if (!empty($this->filters['date_to'])) {
            $query->where('letters.date_computed', '<=', $this->filters['date_to']);
        }
    
        if (!empty($this->filters['author'])) {
            $query->where('authors.name', 'like', '%' . $this->filters['author'] . '%');
        }
    
        if (!empty($this->filters['recipient'])) {
            $query->where('recipients.name', 'like', '%' . $this->filters['recipient'] . '%');
        }
    
        if (!empty($this->filters['origin'])) {
            $query->where('origins.name', 'like', '%' . $this->filters['origin'] . '%');
        }
    
        if (!empty($this->filters['destination'])) {
            $query->where('destinations.name', 'like', '%' . $this->filters['destination'] . '%');
        }
    
        if (!empty($this->filters['repository'])) {
            $query->where('repositories.name', 'like', '%' . $this->filters['repository'] . '%');
        }
    
        if (!empty($this->filters['archive'])) {
            $query->where('archives.name', 'like', '%' . $this->filters['archive'] . '%');
        }
    
        if (!empty($this->filters['collection'])) {
            $query->where('collections.name', 'like', '%' . $this->filters['collection'] . '%');
        }
    
        if (!empty($this->filters['keyword'])) {
            $query->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(keywords.name, '$.cs'))) LIKE ?", ['%' . strtolower($this->filters['keyword']) . '%'])
                ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(keywords.name, '$.en'))) LIKE ?", ['%' . strtolower($this->filters['keyword']) . '%']);
        }
    
        if (!empty($this->filters['status'])) {
            $query->where('letters.status', '=', $this->filters['status']);
        }
    
        if (!empty($this->filters['approval'])) {
            $query->where('letters.approval', '=', $this->filters['approval']);
        }
    
        if (!empty($this->filters['editor'])) {
            $query->where('editors.name', 'like', '%' . $this->filters['editor'] . '%');
        }
    
        if (!empty($this->filters['full_text'])) {
            $query->where('letters.full_text', 'like', '%' . $this->filters['full_text'] . '%');
        }
    
        // 🔹 **Apply Grouping**
        $query->groupBy('letters.id');
    
        // 🔹 **Sorting**
        $order = $this->sorting['order'] ?? 'updated_at';
        $direction = $this->sorting['direction'] ?? 'desc';
    
        switch ($order) {
            case 'author':
                $query->orderBy('author_name', $direction);
                break;
            case 'recipient':
                $query->orderBy('recipient_name', $direction);
                break;
            case 'origin':
                $query->orderBy('origin_name', $direction);
                break;
            case 'destination':
                $query->orderBy('destination_name', $direction);
                break;
            
            case 'keyword':
                $query->orderBy('keyword_name', $direction);
                break;
            case 'media':
                if ($mediaTableExists) {
                    $query->orderBy('media_count', $direction);
                }
                break;
            default:
                $query->orderBy("letters.{$order}", $direction);
                break;
        }
    
        return $query->paginate(25);
    }    

    protected function formatTableData($data): array
    {
        return [
            'header' => ['', 'ID', __('hiko.date'), __('hiko.signature'), __('hiko.author'), __('hiko.recipient'), __('hiko.origin'), __('hiko.destination'), __('hiko.keywords'), __('hiko.media'), __('hiko.status'), __('hiko.approval')],
            'rows' => $data->map(function ($letter) {
                $identities = $letter->identities->groupBy('pivot.role')->toArray();
                $places = $letter->places->groupBy('pivot.role')->toArray();
                $showPublicUrl = $letter->status === 'publish' && !empty(config('hiko.public_url'));

                return [
                    [
                        'component' => [
                            'args' => [
                                'id' => $letter->id,
                                'history' => $letter->history,
                            ],
                            'name' => 'tables.letter-actions',
                        ],
                    ],
                    [
                        'label' => $letter->id,
                    ],
                    [
                        'label' => $letter->pretty_date,
                    ],
                    [
                        'label' => collect($letter->copies)->pluck('signature')->toArray(),
                    ],
                    [
                        'label' => collect($identities['author'] ?? [])->pluck('name')->toArray(),
                    ],
                    [
                        'label' => collect($identities['recipient'] ?? [])->pluck('name')->toArray(),
                    ],
                    [
                        'label' => collect($places['origin'] ?? [])->pluck('name')->toArray(),
                    ],
                    [
                        'label' => collect($places['destination'] ?? [])->pluck('name')->toArray(),
                    ],
                    [
                        'label' => collect($letter->keywords)->map(function ($kw) {
                            return $kw->getTranslation('name', config('hiko.metadata_default_locale'));
                        })->toArray(),
                    ],
                    [
                        'label' => $letter->media->count(),
                    ],
                    [
                        'label' => __("hiko.{$letter->status}"),
                        'link' => $showPublicUrl ? config('hiko.public_url') . '?letter=' . $letter->uuid : '',
                        'external' => $showPublicUrl,
                    ],
                    [
                        'label' => $letter->approval === Letter::APPROVED
                            ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">'. __('hiko.approved') .'</span>'
                            : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">'. __('hiko.not_approved') .'</span>',
                        'link' => '',
                        'external' => false,
                    ],
                ];
            })->toArray(),
        ];
    }

    protected function mapLetterToRow(Letter $letter): array
    {
        $identities = $letter->identities->groupBy('pivot.role')->toArray();
        $places = $letter->places->groupBy('pivot.role')->toArray();
        $showPublicUrl = $letter->status === 'publish' && !empty(config('hiko.public_url'));

        return [
            [
                'component' => [
                    'args' => [
                        'id' => $letter->id,
                        'history' => $letter->history,
                    ],
                    'name' => 'tables.letter-actions',
                ],
            ],
            ['label' => $letter->id],
            ['label' => $letter->pretty_date],
            ['label' => collect($letter->copies)->pluck('signature')->toArray()],
            ['label' => collect($identities['author'] ?? [])->pluck('name')->toArray()],
            ['label' => collect($identities['recipient'] ?? [])->pluck('name')->toArray()],
            ['label' => collect($places['origin'] ?? [])->pluck('name')->toArray()],
            ['label' => collect($places['destination'] ?? [])->pluck('name')->toArray()],
            [
                'label' => collect($letter->keywords)
                    ->map(fn($kw) => $kw->getTranslation('name', config('hiko.metadata_default_locale')))
                    ->toArray(),
            ],
            ['label' => $letter->media->count()],
            [
                'label' => __("hiko.{$letter->status}"),
                'link' => $showPublicUrl ? config('hiko.public_url') . '?letter=' . $letter->uuid : '',
                'external' => $showPublicUrl,
            ],
            [
                'label' => $letter->approval === Letter::APPROVED
                    ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">'. __('hiko.approved') .'</span>'
                    : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">'. __('hiko.not_approved') .'</span>',
                'link' => '',
                'external' => false,
            ],
        ];
    }
}
