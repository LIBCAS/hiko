<?php

namespace App\Livewire;

use App\Models\Letter;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class LettersTable extends Component
{
    use WithPagination;

    public array $filters = [];
    public array $sorting = ['order' => 'updated_at', 'direction' => 'desc'];

    protected $listeners = ['filtersChanged', 'sortingChanged', 'removeFilter', 'resetLettersTablePage'];

    protected array $allowedFilters = [
        'id', 'signature', 'author', 'recipient',
        'origin', 'destination', 'repository', 'archive', 'collection',
        'keyword', 'mentioned', 'content_stripped', 'abstract',
        'languages', 'note', 'media', 'status', 'approval', 'editor',
        'after', 'before'
    ];

    protected array $allowedSorting = [
        'id', 'updated_at', 'author', 'recipient', 'origin', 'destination', 'media'
    ];

    public function mount()
    {
        $this->filters = array_intersect_key(session()->get('lettersTableFilters', []), array_flip($this->allowedFilters));
        $this->sorting = session()->get('lettersTableSorting', $this->sorting);
    }

    public function filtersChanged(array $filters)
    {
        $this->filters = array_intersect_key($filters, array_flip($this->allowedFilters));
        session()->put('lettersTableFilters', $this->filters);
        $this->resetPage();
    }

    public function sortingChanged(array $sorting)
    {
        $order = $sorting['order'] ?? 'updated_at';
        $direction = strtolower($sorting['direction'] ?? 'desc');

        if (in_array($order, $this->allowedSorting) && in_array($direction, ['asc', 'desc'])) {
            $this->sorting = ['order' => $order, 'direction' => $direction];
            session()->put('lettersTableSorting', $this->sorting);
        }

        $this->resetPage();
    }

    public function removeFilter($key)
    {
        if (is_array($key) && isset($key['filterKey'])) {
            $key = $key['filterKey'];
        }

        if (in_array($key, $this->allowedFilters)) {
            unset($this->filters[$key]);
            session()->put('lettersTableFilters', $this->filters);
        }

        $this->resetPage();
    }

    public function resetLettersTablePage() { $this->resetPage(); }

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

        $query = Letter::query()
            ->select("$lettersTable.*")
            ->with([
                'identities', 'places', 'localKeywords', 'globalKeywords', 'media', 'users'
            ])
            ->from($lettersTable);

        $this->applyFilters($query, $tenantPrefix);
        $this->applySorting($query, $tenantPrefix);

        return $query->paginate(25);
    }

    protected function applyFilters($query, $prefix)
    {
        $filters = $this->filters;

        if (!empty($filters['id'])) {
            $query->where("{$prefix}letters.id", $filters['id']);
        }

        if (!empty($filters['signature'])) {
            $query->whereRaw("JSON_EXTRACT(copies, '$[*].signature') LIKE ?", ["%{$filters['signature']}%"]);
        }

        if (!empty($filters['author']) || !empty($filters['recipient'])) {
            $query->whereExists(function ($sub) use ($filters, $prefix) {
                $sub->select(DB::raw(1))
                    ->from("{$prefix}identity_letter")
                    ->join("{$prefix}identities", "{$prefix}identity_letter.identity_id", '=', "{$prefix}identities.id")
                    ->whereColumn("{$prefix}identity_letter.letter_id", "{$prefix}letters.id")
                    ->when(!empty($filters['author']), fn($q) => $q
                        ->where('role', 'author')
                        ->where('name', 'like', '%' . $filters['author'] . '%'))
                    ->when(!empty($filters['recipient']), fn($q) => $q
                        ->orWhere(fn($qq) => $qq
                            ->where('role', 'recipient')
                            ->where('name', 'like', '%' . $filters['recipient'] . '%')));
            });
        }

        if (!empty($filters['origin']) || !empty($filters['destination'])) {
            $query->whereExists(function ($sub) use ($filters, $prefix) {
                $sub->select(DB::raw(1))
                    ->from("{$prefix}letter_place")
                    ->join("{$prefix}places", "{$prefix}letter_place.place_id", '=', "{$prefix}places.id")
                    ->whereColumn("{$prefix}letter_place.letter_id", "{$prefix}letters.id")
                    ->when(!empty($filters['origin']), fn($q) => $q
                        ->where('role', 'origin')
                        ->where(function ($qq) use ($filters) {
                            $qq->where('name', 'like', '%' . $filters['origin'] . '%');
                            for ($i = 0; $i < 50; $i++) {
                                $qq->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(alternative_names, '$[$i]')) LIKE ?", ["%{$filters['origin']}%"]);
                            }
                        }))
                    ->when(!empty($filters['destination']), fn($q) => $q
                        ->orWhere(fn($qq) => $qq
                            ->where('role', 'destination')
                            ->where('name', 'like', '%' . $filters['destination'] . '%')));
            });
        }

        foreach (['repository', 'archive', 'collection', 'mentioned', 'content_stripped', 'abstract', 'note'] as $field) {
            if (!empty($filters[$field])) {
                $query->where("{$prefix}letters.$field", 'like', '%' . $filters[$field] . '%');
            }
        }

        if (!empty($filters['keyword'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('localKeywords', function ($sub) use ($filters) {
                    $sub->where('name->cs', 'like', '%' . $filters['keyword'] . '%')
                        ->orWhere('name->en', 'like', '%' . $filters['keyword'] . '%');
                })
                ->orWhereHas('globalKeywords', function ($sub) use ($filters) {
                    $sub->where('name->cs', 'like', '%' . $filters['keyword'] . '%')
                        ->orWhere('name->en', 'like', '%' . $filters['keyword'] . '%');
                });
            });
        }

        if (!empty($filters['languages'])) {
            $query->whereJsonContains('languages', $filters['languages']);
        }

        if (!empty($filters['media'])) {
            if ($filters['media'] === '1') {
                $query->has('media');
            } elseif ($filters['media'] === '0') {
                $query->doesntHave('media');
            }
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['approval']) && $filters['approval'] !== '') {
            $query->where('approval', $filters['approval']);
        }

        if (!empty($filters['after'])) {
            $query->whereDate('date_computed', '>=', $filters['after']);
        }

        if (!empty($filters['before'])) {
            $query->whereDate('date_computed', '<=', $filters['before']);
        }

        if (!empty($filters['editor']) && $filters['editor'] === 'my' && auth()->check()) {
            $query->where('user_id', auth()->id());
        } elseif (!empty($filters['editor'])) {
            $query->whereHas('users', fn($q) => $q->where('name', 'like', '%' . $filters['editor'] . '%'));
        }
    }

    protected function applySorting($query, $prefix)
    {
        $order = $this->sorting['order'] ?? 'updated_at';
        $direction = $this->sorting['direction'] ?? 'desc';

        switch ($order) {
            case 'author':
            case 'recipient':
                $query->orderBy(
                    DB::table("{$prefix}identity_letter")
                        ->select('name')
                        ->join("{$prefix}identities", "{$prefix}identity_letter.identity_id", '=', "{$prefix}identities.id")
                        ->where("{$prefix}identity_letter.role", '=', $order)
                        ->whereColumn("{$prefix}identity_letter.letter_id", "{$prefix}letters.id")
                        ->limit(1),
                    $direction
                );
                break;

            case 'origin':
            case 'destination':
                $query->orderBy(
                    DB::table("{$prefix}letter_place")
                        ->select('name')
                        ->join("{$prefix}places", "{$prefix}letter_place.place_id", '=', "{$prefix}places.id")
                        ->where("{$prefix}letter_place.role", '=', $order)
                        ->whereColumn("{$prefix}letter_place.letter_id", "{$prefix}letters.id")
                        ->limit(1),
                    $direction
                );
                break;

            case 'media':
                $query->orderBy(
                    DB::table("{$prefix}media")
                        ->selectRaw('count(*)')
                        ->whereColumn("{$prefix}media.model_id", "{$prefix}letters.id")
                        ->where("{$prefix}media.model_type", Letter::class),
                    $direction
                );
                break;

            default:
                $query->orderBy($order, $direction);
                break;
        }
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
                        'label' => collect($letter->all_keywords)->map(function ($kw) {
                            $name = $kw->getTranslation('name', config('hiko.metadata_default_locale'));
                            return $kw->type === 'global' ? "{$name} (G)" : "{$name} (L)";
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
