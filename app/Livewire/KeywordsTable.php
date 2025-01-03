<?php

namespace App\Livewire;

use App\Models\Keyword;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class KeywordsTable extends Component
{
    use WithPagination;

    public $filters = [
        'order' => 'cs',
        'source' => 'all', // 'local', 'global', 'all'
        'cs' => '',
        'en' => '',
        'category' => '',
    ];

    public function search()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
    }

    public function render()
    {
        $keywords = $this->findKeywords();

        return view('livewire.keywords-table', [
            'tableData' => $this->formatTableData($keywords),
            'pagination' => $keywords,
        ]);
    }

    protected function findKeywords(): LengthAwarePaginator
    {
        $filters = $this->filters;
        $perPage = 10;

        $tenantKeywordsQuery = $this->getTenantKeywordsQuery();
        $globalKeywordsQuery = $this->getGlobalKeywordsQuery();

         $query = match($filters['source']){
            'local' => $tenantKeywordsQuery,
            'global' => $globalKeywordsQuery,
             default => $this->mergeQueries($tenantKeywordsQuery, $globalKeywordsQuery),
        };

        if ($filters['order'] === 'cs' || $filters['order'] === 'en') {
            $orderColumn = "name->{$filters['order']}";
             $query->orderBy($orderColumn);
       }

        return $query->paginate($perPage);
    }

    protected function mergeQueries($tenantKeywordsQuery, $globalKeywordsQuery): Builder
    {
           // get the underlying query
          $unionQuery = $tenantKeywordsQuery->toBase();
          $unionQuery->unionAll($globalKeywordsQuery->toBase());


           // create a base query to work with
           $query =  Keyword::query()->from(DB::raw('('.$unionQuery->toSql().') as keywords'));
           // need to set the source manually, so we can map results later to the right model
           $query->select(
               'id',
               'keyword_category_id',
               'name',
               'source',
           );

            foreach ($unionQuery->getBindings() as $binding) {
              $query->addBinding($binding);
            }


           return $query;

    }


    protected function getTenantKeywordsQuery()
    {
        $filters = $this->filters;

        $tenantKeywords = Keyword::with('keyword_category')
            ->select(
                'id',
                'keyword_category_id',
                'name',
                DB::raw("'local' AS source")
            );

        // Apply search filters
        if (!empty($filters['cs'])) {
            $csFilter = strtolower($filters['cs']);
            $tenantKeywords->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) LIKE ?", ["%{$csFilter}%"]);
        }

        if (!empty($filters['en'])) {
            $enFilter = strtolower($filters['en']);
            $tenantKeywords->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE ?", ["%{$enFilter}%"]);
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $categoryFilter = strtolower($filters['category']);
            $tenantKeywords->whereHas('keyword_category', function ($query) use ($categoryFilter) {
                $query->searchByName($categoryFilter);
            });
        }

        return $tenantKeywords;
    }

    protected function getGlobalKeywordsQuery()
    {
        $filters = $this->filters;

        $globalKeywords = \App\Models\GlobalKeyword::with('keyword_category')
            ->select(
                'id',
                'name',
                'keyword_category_id',
                 DB::raw("'global' AS source")
            );

        // Apply search filters
         if (!empty($filters['cs'])) {
            $csFilter = strtolower($filters['cs']);
            $globalKeywords->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"cs\"'))) LIKE ?", ["%{$csFilter}%"]);
        }

        if (!empty($filters['en'])) {
            $enFilter = strtolower($filters['en']);
            $globalKeywords->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"en\"'))) LIKE ?", ["%{$enFilter}%"]);
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $categoryFilter = strtolower($filters['category']);
            $globalKeywords->whereHas('keyword_category', function ($query) use ($categoryFilter) {
                $query->searchByName($categoryFilter);
            });
        }

        return $globalKeywords;
    }

    protected function formatTableData($data): array
    {
        return [
            'header' => auth()->user()->cannot('manage-metadata')
                ? [__('hiko.source'), 'CS', 'EN', __('hiko.category')]
                : ['', __('hiko.source'), 'CS', 'EN', __('hiko.category')],
            'rows' => $data->map(function ($pf) {
                 if($pf->source === 'local'){
                    $keyword = Keyword::find($pf->id);
                }else{
                    $keyword = \App\Models\GlobalKeyword::find($pf->id);
                }
                $csName = $keyword->getTranslation('name', 'cs') ?? 'No CS name';
                $enName = $keyword->getTranslation('name', 'en') ?? 'No EN name';
                $sourceLabel = $pf->source === 'local'
                    ? "<span class='inline-block text-blue-600 border border-blue-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.local')."</span>"
                    : "<span class='inline-block bg-red-100 text-red-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.global')."</span>";
                $categoryDisplay = $keyword->keyword_category
                    ? $keyword->keyword_category->getTranslation('name', 'cs') ?? ''
                    : __('hiko.no_category');


                if ($pf->source === 'local') {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => route('keywords.edit', $pf->id),
                    ];
                } elseif ($pf->source === 'global' && auth()->user()->can('manage-users')) {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => route('global.keywords.edit', $pf->id),
                    ];
                } else {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => '#',
                        'disabled' => true,
                    ];
                }

                $row = auth()->user()->cannot('manage-metadata') ? [] : [$editLink];
                $row[] = ['label' => $sourceLabel];
                $row = array_merge($row, [
                    ['label' => $csName],
                    ['label' => $enName],
                    ['label' => $categoryDisplay],
                ]);

                return $row;
            })->toArray(),
        ];
    }
}
