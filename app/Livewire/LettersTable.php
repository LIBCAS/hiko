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

    public array $filters = [
        'order' => 'updated_at',
        'direction' => 'desc',
    ];

    protected $listeners = ['filtersChanged', 'removeFilter', 'resetLettersTablePage'];

    public function search()
    {
        session()->put('lettersTableFilters', $this->filters);
        $this->dispatch('filtersChanged', filters: $this->filters);
    }

    public function mount()
    {
        if (session()->has('lettersTableFilters')) {
            $this->filters = session()->get('lettersTableFilters');
        } else {
            // Ensure default values are set if no filters are in the session
            $this->filters['order'] = 'updated_at';
            $this->filters['direction'] = 'desc';
        }
    }

    public function render()
    {
        $letters = $this->findLetters();
        return view('livewire.letters-table', [
            'tableData' => $this->formatTableData($letters),
            'pagination' => $letters,
        ]);
    }

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
    }

    public function filtersChanged(array $filters)
    {
        $this->filters = $filters;
        $this->resetPage();
    }

    public function removeFilter(string $filterKey)
    {
        unset($this->filters[$filterKey]);
        $this->resetPage();
    }

    public function resetLettersTablePage()
    {
        $this->resetPage();
    }

    protected function findLetters(): LengthAwarePaginator
    {
        $query = Letter::with([
            'identities' => function ($subquery) {
                $subquery->select('name', 'related_names')
                    ->where(function ($q) {
                        $q->where('role', '=', 'author')
                          ->orWhere('role', '=', 'recipient');
                    })
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

        $order = $this->filters['order'] ?? 'updated_at';
        $direction = $this->filters['direction'] ?? 'desc';

        return $query
            ->orderBy($order, $direction)
            ->paginate(25);
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
}
