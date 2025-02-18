<?php

namespace App\Livewire;

use App\Models\Letter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;
use Illuminate\Support\Facades\DB;

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
    
        $query = Letter::with([
            'identities' => function ($subquery) {
                $subquery->select('name', 'related_names')
                    ->where('role', '=', 'author')
                    ->orWhere('role', '=', 'recipient')
                    ->orderBy('position');
            },
            'places' => function ($subquery) {
                $subquery->select('name')->orderBy('position');
            },
            'keywords' => function ($subquery) {
                $subquery->select('name');
            },
            'media' => function ($subquery) {
                $subquery->select('id', 'model_id', 'model_type')
                    ->where('model_type', Letter::class);
            },
            'users' => function ($subquery) {
                $subquery->select('users.id', 'name');
            },
        ])
        ->select('id', 'uuid', 'history', 'copies', 'date_year', 'date_month', 'date_day', 'date_computed', 'status', 'approval');
    
        $query->filter($this->filters);
    
        $order = $this->sorting['order'] ?? 'updated_at'; // Default order
        $direction = $this->sorting['direction'] ?? 'desc'; // Default direction
    
        switch ($order) {
            case 'author':
                $query->orderBy(
                    DB::table("{$identityLetterTable}")
                        ->select('name')
                        ->join("{$identitiesTable}",
                            "{$identityLetterTable}.identity_id", '=',
                            "{$identitiesTable}.id"
                        )
                        ->where("{$identityLetterTable}.role", '=', 'author')
                        ->whereColumn("{$identityLetterTable}.letter_id", "{$lettersTable}.id")
                        ->limit(1),
                    $direction
                );
                break;
            case 'recipient':
                $query->orderBy(
                    DB::table("{$identityLetterTable}")
                        ->select('name')
                        ->join("{$identitiesTable}",
                            "{$identityLetterTable}.identity_id", '=',
                            "{$identitiesTable}.id"
                        )
                        ->where("{$identityLetterTable}.role", '=', 'recipient')
                        ->whereColumn("{$identityLetterTable}.letter_id", "{$lettersTable}.id")
                        ->limit(1),
                    $direction
                );
                break;
            case 'origin':
                $query->orderBy(
                    DB::table("{$letterPlaceTable}")
                        ->select('name')
                        ->join("{$placesTable}",
                            "{$letterPlaceTable}.place_id", '=',
                            "{$placesTable}.id"
                        )
                        ->where("{$letterPlaceTable}.role", '=', 'origin')
                        ->whereColumn("{$letterPlaceTable}.letter_id", "{$lettersTable}.id")
                        ->limit(1),
                    $direction
                );
                break;
            case 'destination':
                $query->orderBy(
                    DB::table("{$letterPlaceTable}")
                        ->select('name')
                        ->join("{$placesTable}",
                            "{$letterPlaceTable}.place_id", '=',
                            "{$placesTable}.id"
                        )
                        ->where("{$letterPlaceTable}.role", '=', 'destination')
                        ->whereColumn("{$letterPlaceTable}.letter_id", "{$lettersTable}.id")
                        ->limit(1),
                    $direction
                );
            case 'media':
                $query->orderBy(
                    DB::table("{$tenantPrefix}media")
                        ->selectRaw('count(*)')
                        ->whereColumn("{$tenantPrefix}media.model_id", "{$lettersTable}.id")
                        ->where("{$tenantPrefix}media.model_type", Letter::class),
                    $direction
                );
                break;
            default:
                $query->orderBy($order, $direction); // Order by direct column
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
