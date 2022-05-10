<?php

namespace App\Http\Livewire;

use App\Models\Letter;
use Livewire\Component;
use Livewire\WithPagination;

class LettersTable extends Component
{
    use WithPagination;

    public $filters = [
        'order' => 'updated_at',
        'direction' => 'desc',
    ];

    public function search()
    {
        $this->resetPage();
        $this->emit('filtersChanged', $this->filters);
    }

    public function render()
    {
        $letters = $this->findLetters();

        return view('livewire.letters-table', [
            'tableData' => $this->formatTableData($letters),
            'pagination' => $letters,
        ]);
    }

    protected function findLetters()
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
            ->select('id', 'history', 'copies', 'date_year', 'date_month', 'date_day', 'date_computed', 'status');

        $query->filter($this->filters, config('hiko.metadata_default_locale'));

        return $query
            ->orderBy($this->filters['order'], $this->filters['direction'])
            ->paginate(10);
    }

    protected function formatTableData($data)
    {
        return [
            'header' => ['', 'ID', __('hiko.date'), __('hiko.signature'), __('hiko.author'), __('hiko.recipient'), __('hiko.origin'), __('hiko.destination'), __('hiko.keywords'), __('hiko.media'), __('hiko.status')],
            'rows' => $data->map(function ($letter) {
                $identities = $letter->identities->groupBy('pivot.role')->toArray();
                $places = $letter->places->groupBy('pivot.role')->toArray();

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
                        'label' => collect(isset($identities['author']) ? $identities['author'] : [])->pluck('name')->ToArray(),
                    ],
                    [
                        'label' => collect(isset($identities['recipient']) ? $identities['recipient'] : [])->pluck('name')->ToArray(),
                    ],
                    [
                        'label' => collect(isset($places['origin']) ? $places['origin'] : [])->pluck('name')->ToArray(),
                    ],
                    [
                        'label' => collect(isset($places['destination']) ? $places['destination'] : [])->pluck('name')->ToArray(),
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
                    ],
                ];
            })->toArray(),
        ];
    }
}
