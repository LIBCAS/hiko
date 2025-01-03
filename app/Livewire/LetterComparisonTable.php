<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Letter;
use App\Models\Tenant;
use App\Services\LetterComparisonService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Stancl\Tenancy\Facades\Tenancy;

class LetterComparisonTable extends Component
{
    use WithPagination;

    public $filters = [
        'compare_type' => 'full_text',
        'tenant_to_compare' => '',
        'order' => 'similarity',
        'direction' => 'desc',
    ];

    public $tenants = [];
    protected ?LetterComparisonService $comparisonService = null; // Nullable property

    /**
     * Mount the component and initialize properties.
     */
    public function mount(LetterComparisonService $comparisonService)
    {
        $this->comparisonService = $comparisonService;

        // Fetch tenants dynamically based on the tenancy context
        $this->tenants = tenancy()->initialized
            ? Tenancy::central(fn () => Tenant::pluck('name')->toArray())
            : Tenant::pluck('name')->toArray();
    }

    /**
     * Render the Livewire component view.
     */
    public function render()
    {
        $letters = $this->fetchComparisonResults();

        return view('livewire.letter-comparison-table', [
            'tableData' => $this->formatTableData($letters->items()),
            'pagination' => $letters,
        ]);
    }

    /**
     * Fetch letters and calculate similarity.
     */
    protected function fetchComparisonResults(): LengthAwarePaginator
    {
        if (!$this->comparisonService) {
            throw new \LogicException('Comparison service is not initialized.');
        }

        $tenantPrefix = tenancy()->tenant->table_prefix ?? '';
        $tenantToCompare = $this->filters['tenant_to_compare'];

        if (empty($tenantToCompare)) {
            return new LengthAwarePaginator([], 0, 10, $this->getPage());
        }

        $results = $this->comparisonService->search(
            $this->filters['compare_type'],
            $tenantToCompare,
            $tenantPrefix
        );

        // Convert results to a paginated collection
        return $this->paginateCollection(collect($results), 10, $this->getPage());
    }

    /**
     * Re-initialize the comparison service on each request from Livewire.
     */
    public function hydrate()
    {
        $this->comparisonService = app(LetterComparisonService::class);
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
            'direction' => 'desc',
        ];
        $this->search();
    }

    /**
     * Paginate a given collection.
     */
    protected function paginateCollection(Collection $collection, int $perPage, int $currentPage): LengthAwarePaginator
    {
        $currentPageItems = $collection->forPage($currentPage, $perPage);

        return new LengthAwarePaginator(
            $currentPageItems->values(),
            $collection->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    /**
     * Format table data for the template.
     */
    protected function formatTableData(array $results): array
    {
        // Extract all letter_ids that appeared in the results
        $letterIds = collect($results)->pluck('letter_id')->unique()->values();
    
        // Load all these letters with their relationships
        $letters = Letter::with(['authors', 'recipients'])
            ->whereIn('id', $letterIds)
            ->get()
            ->keyBy('id'); // key by id for easy lookup
    
        $header = ['', 'ID', __('hiko.date'), __('hiko.status'), __('hiko.author'), __('hiko.recipient'), __('hiko.similarity')];
    
        $rows = [];
        foreach ($results as $result) {
            $letter = $letters->get($result['letter_id']);
            
            // If letter not found, skip or handle gracefully
            if (!$letter) {
                continue;
            }
    
            // Extract data
            $date = $letter->pretty_date ?: '?'; 
            $status = $letter->status ?: '-';
            $authors = $letter->authors->pluck('name')->toArray();
            $recipients = $letter->recipients->pluck('name')->toArray();
            $similarity = $result['similarity'] . '%';
    
            // If you'd rather show '-' when no authors/recipients:
            // $authors = $authors ?: ['-'];
            // $recipients = $recipients ?: ['-'];
    
            $rows[] = [
                [
                    'label' => __('hiko.edit'),
                    'link' => route('letters.edit', $letter->id),
                ],
                ['label' => $letter->id],
                ['label' => $date],
                ['label' => $status],
                ['label' => $authors],
                ['label' => $recipients],
                ['label' => $similarity],
            ];
        }
    
        return [
            'header' => $header,
            'rows' => $rows,
        ];
    }    

    /**
     * Validate filters before performing search.
     */
    protected function validateFilters()
    {
        $this->validate([
            'filters.compare_type' => 'required|in:full_text,other_columns',
            'filters.tenant_to_compare' => 'nullable|string|exists:tenants,name',
            'filters.order' => 'required|in:similarity,letter_id,date_computed',
            'filters.direction' => 'required|in:asc,desc',
        ]);
    }
}
