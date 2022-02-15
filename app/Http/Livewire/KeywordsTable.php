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
        $query = Keyword::select('id', 'keyword_category_id', 'name', DB::raw("LOWER(JSON_EXTRACT(name, '$.cs')) AS cs"), DB::raw("LOWER(JSON_EXTRACT(name, '$.en')) AS en"))
            ->with([
                'keyword_category' => function ($subquery) {
                    $subquery->select('id', 'name');
                },
            ]);

        if (isset($this->filters['cs']) && !empty($this->filters['cs'])) {
            $query->whereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ["%{$this->filters['cs']}%"]);
        }

        if (isset($this->filters['en']) && !empty($this->filters['en'])) {
            $query->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ["%{$this->filters['en']}%"]);
        }

        if (isset($this->filters['category']) && !empty($this->filters['category'])) {
            $query->whereHas('keyword_category', function ($subquery) {
                $subquery
                    ->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ["%{$this->filters['category']}%"])
                    ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ["%{$this->filters['category']}%"]);
            });
        }

        $query->orderBy($this->filters['order']);

        return $query->paginate(10, ['*'], 'keywordsPage');
    }

    protected function formatTableData($data)
    {
        return [
            'header' => ['', 'CS', 'EN', __('hiko.category')],
            'rows' => $data->map(function ($kw) {
                return [
                    [
                        'label' => __('hiko.edit'),
                        'link' => route('keywords.edit', $kw->id),
                    ],
                    [
                        'label' => $kw->getTranslation('name', 'cs'),
                    ],
                    [
                        'label' => $kw->getTranslation('name', 'en'),
                    ],
                    [
                        'label' => $kw->keyword_category ? implode('-', array_values($kw->keyword_category->getTranslations('name'))) : '',
                    ],
                ];
            })->toArray(),
        ];
    }
}
