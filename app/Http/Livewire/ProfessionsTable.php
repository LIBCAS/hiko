<?php

namespace App\Http\Livewire;

use App\Models\Profession;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfessionsTable extends Component
{
    use WithPagination;

    public $filters = [
        'order' => 'cs',
    ];

    public function search()
    {
        $this->resetPage('professionsPage');
    }

    public function render()
    {
        $professions = $this->findProfessions();

        // Log the table data to ensure it includes both tenant and global professions
        Log::info('Table Data:', $this->formatTableData($professions));

        return view('livewire.professions-table', [
            'tableData' => $this->formatTableData($professions),
            'pagination' => $professions,
        ]);
    }    

    protected function findProfessions()
    {
        // Fetch tenant-specific professions with 'source' as 'local'
        $tenantProfessions = Profession::with(['profession_category' => function ($subquery) {
            $subquery->select('id', 'name');
        }])
            ->select(
                'id',
                'profession_category_id',
                'name',
                DB::raw("'local' AS source"), // Add source field
                DB::raw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) AS cs"),
                DB::raw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) AS en")
            );

        // Fetch global professions with 'source' as 'global'
        $globalProfessions = DB::table('global_professions')
            ->select(
                'id',
                DB::raw('NULL as profession_category_id'),
                'name',
                DB::raw("'global' AS source"), // Add source field
                DB::raw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) AS cs"),
                DB::raw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) AS en")
            );

        // Combine tenant and global professions
        $combinedProfessions = $tenantProfessions->union($globalProfessions)
            ->orderBy($this->filters['order'])
            ->paginate(10, ['*'], 'professionsPage');

        // Log the query results to ensure global and tenant professions are fetched
        Log::info('Tenant professions: ' . $tenantProfessions->toSql());
        Log::info('Global professions: ' . $globalProfessions->toSql());
        Log::info('Combined professions: ', $combinedProfessions->toArray());

        return $combinedProfessions;
    }

    protected function formatTableData($data)
    {
        // Log the entire dataset for debugging purposes
        Log::info('Profession Data:', $data->toArray());

        $header = auth()->user()->cannot('manage-metadata')
            ? ['CS', 'EN', __('hiko.category')]
            : ['', 'CS', 'EN', __('hiko.category')];

        // Return the table data structure
        return [
            'header' => $header,
            'rows' => $data->map(function ($pf) {
                // Log the current profession for debugging
                Log::info('Processing Profession:', ['id' => $pf->id, 'name' => $pf->name]);

                // Access 'cs' and 'en' directly from the fields
                $csName = !empty($pf->cs) ? $pf->cs : 'No CS name';
                $enName = !empty($pf->en) ? $pf->en : 'No EN name';

                // Determine the source label
                $sourceLabel = ucfirst($pf->source); // 'Local' or 'Global'

                // Check if profession category is available
                $category = $pf->profession_category && isset($pf->profession_category->name['cs'])
                    ? $pf->profession_category->name['cs']
                    : __('hiko.no_category');

                // Determine the category source label
                $categorySourceLabel = $pf->source; // Assuming category follows profession source
                // If categories can have different sources, adjust accordingly

                // Build the row data
                if ($pf->source === 'local') {
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => route('professions.edit', $pf->id),
                    ];
                } else {
                    // Global professions are not editable
                    $editLink = [
                        'label' => __('hiko.edit'),
                        'link' => '#',
                        'disabled' => true, // Add a 'disabled' flag
                    ];
                }
                
                $row = auth()->user()->cannot('manage-metadata')
                    ? []
                    : [
                        $editLink,
                    ];

                    $csNameWithLabel = $sourceLabel === 'Local'
                        ? "{$csName} <span class='inline-block color-blue-300 border border-blue-300 text-blue-300 text-xs uppercase ml-2 px-2 py-1 rounded'>Local</span>"
                        : "{$csName} <span class='inline-block bg-red-100 text-red-600 text-xs uppercase ml-2 px-2 py-1 rounded'>Global</span>";
                    
                    $enNameWithLabel = $enName;
                    
                    $categoryWithLabel = $category;
                
                    return array_merge($row, [
                    [
                        'label' => $csNameWithLabel,
                    ],
                    [
                        'label' => $enNameWithLabel,
                    ],
                    [
                        'label' => $categoryWithLabel,
                    ],
                ]);
            })->toArray(),
        ];
    }           
}
