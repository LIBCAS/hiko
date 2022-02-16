<?php

namespace App\Http\Livewire;

use App\Models\Keyword;
use Livewire\Component;
use Illuminate\Support\Str;
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
        $query = Keyword::select('id', 'keyword_category_id', 'name', DB::raw("LOWER(JSON_EXTRACT(name, '$.cs')) AS cs"), DB::raw("LOWER(JSON_EXTRACT(name, '$.en')) AS en"))
            ->with([
                'keyword_category' => function ($subquery) {
                    $subquery->select('id', 'name');
                },
            ]);

        if (isset($this->filters['cs']) && !empty($this->filters['cs'])) {
            $query->whereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($this->filters['cs']) . '%']);
        }

        if (isset($this->filters['en']) && !empty($this->filters['en'])) {
            $query->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($this->filters['en']) . '%']);
        }

        if (isset($this->filters['category']) && !empty($this->filters['category'])) {
            $query->whereHas('keyword_category', function ($subquery) {
                $subquery
                    ->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($this->filters['category']) . '%'])
                    ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($this->filters['category']) . '%']);
            });
        }

        $query->orderBy($this->filters['order']);

        return $query->paginate(10, ['*'], 'keywordsPage');
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
                        'label' => $kw->getTranslation('name', 'cs'),
                    ],
                    [
                        'label' => $kw->getTranslation('name', 'en'),
                    ],
                    [
                        'label' => $kw->keyword_category ? $kw->keyword_category->getTranslation('name', config('hiko.metadata_default_locale')) : '',
                    ],
                ]);
            })->toArray(),
        ];
    }
}
