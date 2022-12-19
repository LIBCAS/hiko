<?php

namespace App\Http\Livewire;

use App\Models\Letter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class LettersTable extends Component
{
    use WithPagination;

    public array $filters = [
        'order' => 'updated_at',
        'direction' => 'desc',
    ];

    public function search()
    {
        $this->resetPage();
        $this->emit('filtersChanged', $this->filters);
        session()->put('lettersTableFilters', $this->filters);
    }

    public function mount()
    {
        if (session()->has('lettersTableFilters')) {
            $this->filters = session()->get('lettersTableFilters');
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
        $this->reset();
        $this->search();
    }

    protected function findLetters(): LengthAwarePaginator
    {
        $query = Letter::with([
            'identities' => function ($subquery) {
                $subquery->select('name', 'alternative_names')
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
                $subquery->select('model_id', 'model_type');
            },
            'users' => function ($subquery) {
                $subquery->select('users.id', 'name');
            },
        ])
            ->select('id', 'uuid', 'history', 'copies', 'date_year', 'date_month', 'date_day', 'date_computed', 'status');

        $query->filter($this->filters);

        return $query
            ->orderBy($this->filters['order'], $this->filters['direction'])
            ->paginate(10);
    }

    protected function formatTableData($data): array
    {
        return [
            'header' => ['', 'ID', __('hiko.date'), __('hiko.signature'), __('hiko.author'), __('hiko.recipient'), __('hiko.origin'), __('hiko.destination'), __('hiko.keywords'), __('hiko.media'), __('hiko.status')],
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
                        'label' => collect($identities['author'] ?? [])->pluck('name')->ToArray(),
                    ],
                    [
                        'label' => collect($identities['recipient'] ?? [])->pluck('name')->ToArray(),
                    ],
                    [
                        'label' => collect($places['origin'] ?? [])->pluck('name')->ToArray(),
                    ],
                    [
                        'label' => collect($places['destination'] ?? [])->pluck('name')->ToArray(),
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
                        'label' => __("hiko.{$letter->status}"),  // public_url
                        'link' => $showPublicUrl ? config('hiko.public_url') . '?letter=' . $letter->uuid : '',
                        'external' => $showPublicUrl,
                    ],
                ];
            })->toArray(),
        ];
    }
}
