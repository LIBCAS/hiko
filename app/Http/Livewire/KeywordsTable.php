<?php

namespace App\Http\Livewire;

use App\Models\Keyword;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class KeywordsTable extends Component
{
    use WithPagination;

    public $filters = [
        'order' => 'cs',
    ];

    public function search()
    {
        $this->resetPage('keywordsPage');
    }

    public function render()
    {
        $keywords = $this->findKeywords();

        return view('livewire.keywords-table', [
            'tableData' => $this->formatTableData($keywords),
            'pagination' => $keywords,
        ]);
    }

    protected function findKeywords()
    {
        return Keyword::with([
            'keyword_category' => function ($subquery) {
                $subquery->select('id', 'name');
            },
        ])
            ->select('id', 'keyword_category_id', 'name', DB::raw("LOWER(JSON_EXTRACT(name, '$.cs')) AS cs"), DB::raw("LOWER(JSON_EXTRACT(name, '$.en')) AS en"))
            ->search($this->filters)
            ->orderBy($this->filters['order'])
            ->paginate(10, ['*'], 'keywordsPage');
    }

    protected function formatTableData($data)
    {
        $header = auth()->user()->cannot('manage-metadata')
            ? ['CS', 'EN', __('hiko.category')]
            : ['', 'CS', 'EN', __('hiko.category')];

        return [
            'header' => $header,
            'rows' => $data->map(function ($kw) {
                $row = auth()->user()->cannot('manage-metadata')
                    ? []
                    : [
                        [
                            'label' => __('hiko.edit'),
                            'link' => route('keywords.edit', $kw->id),
                        ],
                    ];

                return array_merge($row, [
                    [
                        'label' => $kw->getTranslation('name', 'cs', false),
                    ],
                    [
                        'label' => $kw->getTranslation('name', 'en', false),
                    ],
                    [
                        'label' => $kw->keyword_category ? $kw->keyword_category->getTranslation('name', config('hiko.metadata_default_locale', false)) : '',
                    ],
                ]);
            })
                ->toArray(),
        ];
    }
}
