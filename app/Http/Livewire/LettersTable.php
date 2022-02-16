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

        $query->orderBy($this->filters['order']);

        return $query->paginate(10);
    }

    protected function formatTableData($data)
    {
        return [
            'header' => ['', __('hiko.date')],
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
                        'label' => $letter->pretty_date,
                    ],
                ];
            })->toArray(),
        ];
    }
}
