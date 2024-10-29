<?php

namespace App\Http\Livewire;

use App\Models\Profession;
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

        // Sort the collection
        if ($filters['order'] === 'cs' || $filters['order'] === 'en') {
            $professions = $professions->sortBy(function ($item) use ($filters) {
                return strtolower($item->getTranslation('name', $filters['order']) ?? '');
            })->values();
        }

        // Paginate the collection
        $page = $this->professionsPage ?? 1;
        $perPage = 10;
        $total = $professions->count();

        $professionsForPage = $professions->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $professionsForPage,
            $total,
            $perPage,
            $page,
            [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'pageName' => 'professionsPage',
            ]
        );

        return $paginator;
    }

    protected function getTenantProfessions()
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

        return $tenantProfessions->get();
    }

    protected function getGlobalProfessions()
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

        return $globalProfessions->get();
    }

    protected function formatTableData($data)
    {
        $header = auth()->user()->cannot('manage-metadata')
            ? [__('hiko.source'), 'CS', 'EN', __('hiko.category')]
            : ['', __('hiko.source'), 'CS', 'EN', __('hiko.category')];

        return [
            'header' => $header,
            'rows' => $data->map(function ($pf) {
                // Access translations
                $csName = $pf->getTranslation('name', 'cs') ?? 'No CS name';
                $enName = $pf->getTranslation('name', 'en') ?? 'No EN name';

                // Source label
                $sourceLabel = $pf->source === 'local'
                    ? "<span class='inline-block text-blue-600 border border-blue-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.local')."</span>"
                    : "<span class='inline-block bg-red-100 text-red-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.global')."</span>";

                // Profession category name and source
                if ($pf->profession_category) {
                    $categoryName = $pf->profession_category->getTranslation('name', 'cs') ?? '';
                    $categorySource = $pf->profession_category->source ?? 'global';
                    $categorySourceLabel = $categorySource === 'local'
                        ? "<span class='inline-block text-blue-600 border border-blue-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.local')."</span>"
                        : "<span class='inline-block bg-red-100 text-red-600 text-xs uppercase px-2 py-1 rounded'>".__('hiko.global')."</span>";
                    $categoryDisplay = "$categoryName $categorySourceLabel";
                } else {
                    $categoryDisplay = __('hiko.no_category');
                }

                // Build the edit link
                if ($pf->source === 'local') {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => route('professions.edit', $pf->id),
                    ];
                } else {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => '#',
                        'disabled' => true,
                    ];
                }

                // Construct the row
                $row = auth()->user()->cannot('manage-metadata') ? [] : [$editLink];

                $row[] = [
                    'label' => $sourceLabel,
                ];

                $row = array_merge($row, [
                    [
                        'label' => $csName,
                    ],
                    [
                        'label' => $enName,
                    ],
                    [
                        'label' => $categoryDisplay,
                    ],
                ]);

                return $row;
            })->toArray(),
        ];
    }
}
