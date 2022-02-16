<?php

namespace App\Http\Livewire;

use App\Models\Letter;
use Livewire\Component;
use Livewire\WithPagination;

class LettersTable extends Component
{
    use WithPagination;

    public $filters = [
        'order' => 'id',
        'direction' => 'desc',
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
        $query = Letter::with('identities', 'places', 'keywords')
            ->select('id', 'history', 'copies', 'date_year', 'date_month', 'date_day', 'date_computed', 'status');

        $query->orderBy($this->filters['order'], $this->filters['direction']);

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

        return $query->paginate(10);
    }

    protected function formatTableData($data)
    {
        return [
            'header' => ['', 'ID', __('hiko.date'), __('hiko.status')],
            'rows' => $data->map(function ($letter) {
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
                        'label' => __("hiko.{$letter->status}"),
                    ],
                ];
            })->toArray(),
        ];
    }
}
