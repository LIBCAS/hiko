<?php

namespace App\Http\Livewire;

use App\Models\Profession;
use App\Models\GlobalProfession;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

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

    protected function findProfessions()
    {
        $filters = $this->filters;
        $professions = collect();

        // Fetch tenant professions if 'local' or 'all' is selected
        if ($filters['source'] === 'local' || $filters['source'] === 'all') {
            $tenantProfessions = $this->getTenantProfessions();
            $professions = $professions->merge($tenantProfessions);
        }

        // Fetch global professions if 'global' or 'all' is selected
        if ($filters['source'] === 'global' || $filters['source'] === 'all') {
            $globalProfessions = $this->getGlobalProfessions();
            $professions = $professions->merge($globalProfessions);
        }

        // Sort the collection based on locale
        if (in_array($filters['order'], ['cs', 'en'])) {
            $professions = $professions->sortBy(fn($item) => strtolower($item->getTranslation('name', $filters['order']) ?? ''))->values();
        }

        return $this->paginateCollection($professions);
    }

    protected function getTenantProfessions()
    {
        $filters = $this->filters;

        return Profession::with('profession_category')
            ->select('id', 'profession_category_id', 'name', DB::raw("'local' AS source"))
            ->applyFilters($filters) // Using query scopes to filter
            ->get();
    }

    protected function getGlobalProfessions()
    {
        $filters = $this->filters;

        return GlobalProfession::with('profession_category')
            ->select('id', 'name', 'profession_category_id', DB::raw("'global' AS source"))
            ->applyFilters($filters)
            ->get();
    }

    protected function paginateCollection($collection)
    {
        $page = $this->professionsPage ?? 1;
        $perPage = 10;
        $total = $collection->count();

        return new LengthAwarePaginator(
            $collection->forPage($page, $perPage),
            $total,
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'pageName' => 'professionsPage']
        );
    }

    protected function formatTableData($data)
    {
        $header = auth()->user()->cannot('manage-metadata')
            ? [__('hiko.source'), 'CS', 'EN', __('hiko.category')]
            : ['', __('hiko.source'), 'CS', 'EN', __('hiko.category')];
    
        return [
            'header' => $header,
            'rows' => $data->map(function ($profession) {
                // Access translations
                $csName = $profession->getTranslation('name', 'cs') ?? 'No CS name';
                $enName = $profession->getTranslation('name', 'en') ?? 'No EN name';
    
                // Source label
                $sourceLabel = $profession->source === 'local'
                    ? "<span class='inline-block text-blue-600 border border-blue-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.local')."</span>"
                    : "<span class='inline-block bg-red-100 text-red-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.global')."</span>";
    
                // Profession category name
                if ($profession->profession_category) {
                    $categoryName = $profession->profession_category->getTranslation('name', 'cs') ?? '';
                } else {
                    $categoryName = __('hiko.no_category');
                }
    
                // Build the edit link for local professions if user has permission
                if ($profession->source === 'local' && auth()->user()->can('manage-metadata')) {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => route('professions.edit', $profession->id),
                    ];
                } else {
                    $editLink = [
                        'label' => '',
                    ];
                }
    
                // Construct the row
                $row = auth()->user()->cannot('manage-metadata') ? [] : [$editLink];
    
                $row[] = [
                    'label' => $sourceLabel,
                ];
    
                $row[] = [
                    'label' => $csName,
                ];
    
                $row[] = [
                    'label' => $enName,
                ];
    
                $row[] = [
                    'label' => $categoryName,
                ];
    
                return $row;
            })->toArray(),
        ];
    }    

    protected function formatRow($profession)
    {
        return [
            [
                'label' => $this->getSourceLabel($profession->source),
            ],
            [
                'label' => $profession->getTranslation('name', 'cs') ?? 'No CS name',
            ],
            [
                'label' => $profession->getTranslation('name', 'en') ?? 'No EN name',
            ],
            [
                'label' => $profession->profession_category?->getTranslation('name', 'cs') ?? __('hiko.no_category'),
            ],
        ];
    }

    protected function getSourceLabel($source)
    {
        return $source === 'local'
            ? "<span class='inline-block text-blue-600 border border-blue-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.local')."</span>"
            : "<span class='inline-block bg-red-100 text-red-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.global')."</span>";
    }
}
