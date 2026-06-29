<?php

namespace App\Livewire;

use App\Models\Letter;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use App\Services\LetterFilterService;

class LettersTable extends Component
{
    use WithPagination;

    public array $filters = [];
    public array $sorting = ['order' => 'updated_at', 'direction' => 'desc'];

    protected $listeners = ['filtersChanged', 'sortingChanged', 'removeFilter', 'resetLettersTablePage'];

    protected array $allowedFilters = LetterFilterService::ALLOWED_FILTERS;

    protected array $allowedSorting = [
        'id', 'updated_at', 'author', 'recipient', 'origin', 'destination', 'media', 'date_computed', 'abstract'
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
        $query = app(LetterFilterService::class)->filteredQuery($this->filters, [
                'identities', 'localPlaces', 'globalPlaces', 'localKeywords', 'globalKeywords', 'media', 'users'
            ]);
        $this->applySorting($query, $tenantPrefix);

        return $query->paginate(25);
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
                $identities = $letter->identities->groupBy('pivot.role');
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
                        'label' => $this->formatLinkedIdentities($identities->get('author', collect())),
                        'isHtml' => true,
                    ],
                    [
                        'label' => $this->formatLinkedIdentities($identities->get('recipient', collect())),
                        'isHtml' => true,
                    ],
                    [
                        'label' => $this->formatLinkedPlaces($letter->all_origins),
                        'isHtml' => true,
                    ],
                    [
                        'label' => $this->formatLinkedPlaces($letter->all_destinations),
                        'isHtml' => true,
                    ],
                    [
                        'label' => $this->formatLinkedKeywords($letter->all_keywords),
                        'isHtml' => true,
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

    protected function formatLinkedIdentities($identities): string
    {
        return $this->formatLinkedList(collect($identities)->map(function ($identity) {
            return [
                'label' => $identity->name,
                'link' => route('identities.edit', $identity->id),
            ];
        }));
    }

    protected function formatLinkedPlaces($places): string
    {
        return $this->formatLinkedList(collect($places)->map(function ($place) {
            $isGlobal = $place->type === 'global';

            return [
                'label' => $place->name . ($isGlobal ? ' (G)' : ' (L)'),
                'link' => $isGlobal
                    ? route('global.places.edit', $place->id)
                    : route('places.edit', $place->id),
            ];
        }));
    }

    protected function formatLinkedKeywords($keywords): string
    {
        return $this->formatLinkedList(collect($keywords)->map(function ($keyword) {
            $isGlobal = $keyword->type === 'global';
            $name = $this->localizedName($keyword);

            return [
                'label' => $name . ($isGlobal ? ' (G)' : ' (L)'),
                'link' => $isGlobal
                    ? route('global.keywords.edit', $keyword->id)
                    : route('keywords.edit', $keyword->id),
            ];
        }));
    }

    protected function formatLinkedList($items): string
    {
        $items = collect($items)->filter(fn($item) => !empty($item['label']) && !empty($item['link']));

        if ($items->isEmpty()) {
            return '';
        }

        $links = $items->map(function ($item) {
            return sprintf(
                '<li><a href="%s" class="text-primary-dark hover:underline">%s</a></li>',
                e($item['link']),
                e($item['label'])
            );
        })->implode('');

        return '<ul>' . $links . '</ul>';
    }

    protected function localizedName($model): string
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'cs';
        $fallbackLocale = $locale === 'en' ? 'cs' : 'en';

        return trim((string) (
            $model->getTranslation('name', $locale, false)
            ?: $model->getTranslation('name', $fallbackLocale, false)
            ?: ''
        ));
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
                    ->map(fn($kw) => $this->localizedName($kw))
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
