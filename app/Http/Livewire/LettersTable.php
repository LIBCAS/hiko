<?php

namespace App\Http\Livewire;

use App\Models\Letter;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithPagination;

class LettersTable extends Component
{
    use WithPagination;

    public $filters = [
        'order' => 'id',
        'direction' => 'asc',
    ];

    public function search()
    {
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

    protected function findLetters()
    {
        $lang = config('hiko.metadata_default_locale');

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
        ])
            ->select('id', 'history', 'copies', 'date_year', 'date_month', 'date_day', 'date_computed', 'status');

        if (isset($this->filters['id']) && !empty($this->filters['id'])) {
            $query->where('id', 'LIKE', "%" . $this->filters['id'] . "%");
        }

        if (isset($this->filters['status']) && !empty($this->filters['status'])) {
            $query->where('status', '=', $this->filters['status']);
        }

        if (isset($this->filters['after']) && !empty($this->filters['after'])) {
            $query->whereDate('date_computed', '>=', $this->filters['after']);
        }

        if (isset($this->filters['before']) && !empty($this->filters['before'])) {
            $query->whereDate('date_computed', '<=', $this->filters['before']);
        }

        if (isset($this->filters['signature']) && !empty($this->filters['signature'])) {
            $query->whereRaw("LOWER(JSON_EXTRACT(copies, '$[*].signature')) like ?", ['%' . Str::lower($this->filters['signature']) . '%']);
        }

        if (isset($this->filters['author']) && !empty($this->filters['author'])) {
            $query = $this->addIdentityNameFilter($query, 'author');
        }

        if (isset($this->filters['recipient']) && !empty($this->filters['recipient'])) {
            $query = $this->addIdentityNameFilter($query, 'recipient');
        }

        if (isset($this->filters['origin']) && !empty($this->filters['origin'])) {
            $query = $this->addPlaceFilter($query, 'origin');
        }

        if (isset($this->filters['destination']) && !empty($this->filters['destination'])) {
            $query = $this->addPlaceFilter($query, 'destination');
        }

        if (isset($this->filters['keyword']) && !empty($this->filters['keyword'])) {
            $query->whereHas('keywords', function ($subquery) use ($lang) {
                $subquery
                    ->whereRaw("LOWER(JSON_EXTRACT(name, '$.{$lang}')) like ?", ['%' . Str::lower($this->filters['keyword']) . '%']);
            });
        }

        return $query
            ->orderBy($this->filters['order'], $this->filters['direction'])
            ->paginate(10);
    }

    protected function formatTableData($data)
    {


        return [
            'header' => ['', 'ID', __('hiko.date'), __('hiko.signature'), __('hiko.author'), __('hiko.recipient'), __('hiko.origin'), __('hiko.destination'), __('hiko.keywords'), __('hiko.status')],
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
                        'label' => __("hiko.{$letter->status}"),
                    ],
                ];
            })->toArray(),
        ];
    }

    protected function addIdentityNameFilter($query, string $type)
    {
        return $query->whereHas('identities', function ($subquery) use ($type) {
            $subquery
                ->where('role', '=', $type)
                ->where(function ($namesubquery) use ($type) {
                    $namesubquery->where('name', 'LIKE', "%" . $this->filters[$type] . "%")
                        ->orWhereRaw('LOWER(alternative_names) like ?', ['%' . Str::lower($this->filters[$type]) . '%']);
                });
        });
    }

    protected function addPlaceFilter($query, string $type)
    {
        return $query->whereHas('places', function ($subquery) use ($type) {
            $subquery
                ->where('role', '=', $type)
                ->where('name', 'LIKE', "%" . $this->filters[$type] . "%");
        });
    }
}
