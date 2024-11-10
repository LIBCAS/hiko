<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\LetterComparisonService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Models\Tenant;
use Stancl\Tenancy\Facades\Tenancy;

class LetterComparisonTable extends Component
{
    use WithPagination;

    public $filters = [
        'compare_type' => 'full_text',
        'tenant_to_compare' => '',
        'order' => 'similarity',
    ];
    public $tenants;

    protected $comparisonService;

    /**
     * Inject the LetterComparisonService via constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->comparisonService = app(LetterComparisonService::class);
    }

    /**
     * Initialize component by loading tenants.
     */
    public function mount()
    {
        $this->tenants = Tenant::pluck('name')->toArray();
    }

    /**
     * Triggered when search is performed.
     */
    public function search()
    {
        $this->validateFilters();
        $this->resetPage();
    }

    /**
     * Reset filters to default values.
     */
    public function resetFilters()
    {
        $this->filters = [
            'compare_type' => 'full_text',
            'tenant_to_compare' => '',
            'order' => 'similarity',
        ];
        $this->search();
    }

    /**
     * Render the Livewire component view.
     */
    public function render()
    {
        $paginatedResults = $this->fetchComparisonResults();

        return view('livewire.letter-comparison-table', [
            'tableData' => $this->formatTableData($paginatedResults->items()),
            'pagination' => $paginatedResults,
        ]);
    }

    /**
     * Fetch comparison results using the LetterComparisonService.
     */
    protected function fetchComparisonResults()
    {
        if (empty($this->filters['tenant_to_compare'])) {
            return new LengthAwarePaginator([], 0, 10, $this->page);
        }

        try {
            // Retrieve the current tenant's table prefix using the tenancy() helper
            $currentTenant = tenancy()->tenant;

            if (!$currentTenant) {
                throw new \Exception('Current tenant not found.');
            }

            $tenantPrefix = $currentTenant->table_prefix;

            $compareType = $this->filters['compare_type'];
            $tenantToCompare = $this->filters['tenant_to_compare'];

            $results = $this->comparisonService->search($compareType, $tenantToCompare, $tenantPrefix);

            if ($this->filters['order'] === 'similarity') {
                usort($results, function ($a, $b) {
                    return $b['similarity'] <=> $a['similarity'];
                });
            } else if ($this->filters['order'] === 'letter_id') {
                usort($results, function ($a, $b) {
                    return $a['letter_id'] <=> $b['letter_id'];
                });
            }

            $paginatedResults = $this->paginateCollection(collect($results), 15, $this->page);
            return $paginatedResults;
        } catch (\Exception $e) {
            return new LengthAwarePaginator([], 0, 15, $this->page);
        }
    }

    /**
     * Paginate the given collection.
     */
    protected function paginateCollection(Collection $collection, int $perPage, int $currentPage)
    {
        $currentPageItems = $collection->forPage($currentPage, $perPage);
        return new LengthAwarePaginator(
            $currentPageItems,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Format the table data for the view.
     */
    protected function formatTableData($data)
    {
        $header = ['', 'Letter ID', 'Tenant Compared', 'Similarity (%)', 'Match Info'];

        return [
            'header' => $header,
            'rows' => collect($data)->map(function ($result) {
                if (!is_array($result) || !$this->isValidResultStructure($result)) {

                    return [
                        ['label' => __('hiko.error'), 'link' => ''],
                        ['label' => 'N/A'],
                        ['label' => 'N/A'],
                        ['label' => 'N/A'],
                    ];
                }

                $row = [
                    [
                        'label' => __('hiko.edit'),
                        'link' => route('letters.edit', $result['letter_id']),
                    ],
                ];

                return array_merge($row, [
                    ['label' => $result['letter_id']],
                    ['label' => $result['tenant']],
                    ['label' => $result['similarity'] . '%'],
                    ['label' => $result['match_info']],
                ]);
            })->toArray(),
        ];
    }

    /**
     * Validate the structure of the comparison result.
     */
    private function isValidResultStructure(array $result): bool
    {
        $expectedKeys = ['letter_id', 'tenant', 'similarity', 'match_info'];
        return !array_diff_key(array_flip($expectedKeys), $result);
    }

    /**
     * Validate filters before performing search.
     */
    private function validateFilters()
    {
        $this->validate([
            'filters.compare_type' => 'required|in:full_text,other_columns',
            'filters.tenant_to_compare' => 'required|string|exists:tenants,name',
            'filters.order' => 'required|in:similarity,letter_id',
        ]);
    }
}
