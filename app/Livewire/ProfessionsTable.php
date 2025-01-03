<?php

namespace App\Livewire;

use App\Models\Profession;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProfessionsTable extends Component
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
        $this->resetPage('professionsPage');
    }

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
    }

    public function render()
    {
        $professions = $this->findProfessions();

        return view('livewire.professions-table', [
            'tableData' => $this->formatTableData($professions),
            'pagination' => $professions,
        ]);
    }

     protected function findProfessions(): LengthAwarePaginator
    {
        $filters = $this->filters;
        $perPage = 10;

        $tenantProfessionsQuery = $this->getTenantProfessionsQuery();
        $globalProfessionsQuery = $this->getGlobalProfessionsQuery();

        $query = match($filters['source']){
            'local' => $tenantProfessionsQuery,
            'global' => $globalProfessionsQuery,
            default => $this->mergeQueries($tenantProfessionsQuery, $globalProfessionsQuery),
         };

       if ($filters['order'] === 'cs' || $filters['order'] === 'en') {
         $orderColumn = "name->{$filters['order']}";
           $query->orderBy($orderColumn);
        }
        return $query->paginate($perPage);
    }

    protected function mergeQueries($tenantProfessionsQuery, $globalProfessionsQuery): Builder
    {
           // get the underlying query
          $unionQuery = $tenantProfessionsQuery->toBase();
          $unionQuery->unionAll($globalProfessionsQuery->toBase());


           // create a base query to work with
           $query =  Profession::query()->from(DB::raw('('.$unionQuery->toSql().') as professions'));
           // need to set the source manually, so we can map results later to the right model
           $query->select(
               'id',
                'profession_category_id',
               'name',
               'source',
           );

            foreach ($unionQuery->getBindings() as $binding) {
              $query->addBinding($binding);
            }

           return $query;

    }


    protected function getTenantProfessionsQuery()
    {
        $filters = $this->filters;

         $tenantProfessions = Profession::with('profession_category')
            ->select(
                'id',
                'profession_category_id',
                'name',
                DB::raw("'local' AS source")
            );

        // Apply search filters
        if (!empty($filters['cs'])) {
            $csFilter = strtolower($filters['cs']);
             $tenantProfessions->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) LIKE ?", ["%{$csFilter}%"]);
        }

        if (!empty($filters['en'])) {
            $enFilter = strtolower($filters['en']);
             $tenantProfessions->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE ?", ["%{$enFilter}%"]);
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $categoryFilter = strtolower($filters['category']);
            $tenantProfessions->whereHas('profession_category', function ($query) use ($categoryFilter) {
                $query->searchByName($categoryFilter);
            });
        }


        return $tenantProfessions;
    }

    protected function getGlobalProfessionsQuery()
    {
        $filters = $this->filters;

         $globalProfessions = \App\Models\GlobalProfession::with('profession_category')
            ->select(
                'id',
                'name',
                'profession_category_id',
                DB::raw("'global' AS source")
            );

        // Apply search filters
        if (!empty($filters['cs'])) {
            $csFilter = strtolower($filters['cs']);
            $globalProfessions->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"cs\"'))) LIKE ?", ["%{$csFilter}%"]);
        }

        if (!empty($filters['en'])) {
            $enFilter = strtolower($filters['en']);
            $globalProfessions->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"en\"'))) LIKE ?", ["%{$enFilter}%"]);
        }

        // Apply category filter
        if (!empty($filters['category'])) {
            $categoryFilter = strtolower($filters['category']);
           $globalProfessions->whereHas('profession_category', function ($query) use ($categoryFilter) {
                $query->searchByName($categoryFilter);
            });
        }

        return $globalProfessions;
    }

    protected function formatTableData($data)
    {
        return [
            'header' => auth()->user()->cannot('manage-metadata')
                ? [__('hiko.source'), 'CS', 'EN', __('hiko.category')]
                : ['', __('hiko.source'), 'CS', 'EN', __('hiko.category')],
           'rows' => $data->map(function ($pf) {
               if($pf->source === 'local'){
                  $profession = Profession::find($pf->id);
               }else{
                   $profession = \App\Models\GlobalProfession::find($pf->id);
               }
                $csName = $profession->getTranslation('name', 'cs') ?? 'No CS name';
                $enName = $profession->getTranslation('name', 'en') ?? 'No EN name';
                $sourceLabel = $pf->source === 'local'
                    ? "<span class='inline-block text-blue-600 border border-blue-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.local')."</span>"
                    : "<span class='inline-block bg-red-100 text-red-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.global')."</span>";
                $categoryDisplay = $profession->profession_category
                    ? $profession->profession_category->getTranslation('name', 'cs') ?? ''
                    : __('hiko.no_category');

                if ($pf->source === 'local') {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => route('professions.edit', $pf->id),
                    ];
                } elseif ($pf->source === 'global' && auth()->user()->can('manage-users')) {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => route('global.professions.edit', $pf->id),
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
