<?php

namespace App\Http\Livewire;

use App\Services\DuplicateDetectionService;
use Livewire\Component;
use Illuminate\Support\Collection;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

class DuplicatesTable extends Component
{
    use WithPagination;

    public array $filters = [
        'compare' => 'full_texts',
        'threshold' => 0.5,
        'database' => '',
        'perPage' => 10,
    ];

    public string $currentDatabase;
    public Collection $duplicates;
    public bool $showFiltersMessage = true;
    public Collection $options;

    public function mount()
    {
        $defaultConnectionConfig = config('database.connections.' . config('database.default'));
        $databasePrefix = $defaultConnectionConfig['prefix'] ?? '';
        $databasePrefix = str_replace('__', '', $databasePrefix);

        $this->currentDatabase = $databasePrefix;

        $tenants = Tenant::all();
        $options = $tenants->pluck('name', 'table_prefix');
        $this->options = $options;

        $this->filters['database'] = $this->currentDatabase;

        $this->duplicates = collect();
    }

    public function updatedFilters($value, $name)
    {
        // This method will be called automatically by Livewire whenever a filter is updated
        $this->search();
    }

    public function search()
    {
        $this->gotoPage(1);
        $this->duplicates = $this->findDuplicates();
        $this->showFiltersMessage = $this->duplicates->isEmpty();
    }

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
    }

    protected function formatTableData(Collection $data): array
    {
        return [
            'header' => ['Source Database', 'Letter ID', 'Target Database', 'Letter ID', 'Similarity Ratio'],
            'rows' => $data->map(function ($duplicate) {
                $letter1 = isset($duplicate['letter1']) ? (object) $duplicate['letter1'] : (object)['id' => '', 'prefix' => ''];
                $letter2 = isset($duplicate['letter2']) ? (object) $duplicate['letter2'] : (object)['id' => '', 'prefix' => ''];

                return [
                    ['label' => $letter1->prefix],
                    ['label' => $letter1->id],
                    ['label' => $letter2->prefix],
                    ['label' => $letter2->id],
                    ['label' => number_format($duplicate['similarity'], 3)],
                ];
            })->toArray(),
        ];
    }

    protected function findDuplicates(): Collection
    {
        $prefixes = $this->currentDatabase == $this->filters['database'] ? [$this->currentDatabase] : [$this->currentDatabase, $this->filters['database']];
        $duplicateDetectionService = new DuplicateDetectionService($prefixes);
        $duplicates = $duplicateDetectionService->processDuplicates($this->filters['compare']);
        $duplicates = collect($duplicates);
        return $this->applyFilters($duplicates);
    }

    protected function applyFilters(Collection $duplicates): Collection
    {
        return $duplicates->filter(function ($duplicate) {
            if (!$this->passesCompareFilter($duplicate)) {
                return false;
            }

            if (!$this->passesThresholdFilter($duplicate)) {
                return false;
            }

            return true;
        });
    }

    protected function passesCompareFilter($duplicate): bool
    {
        if ($this->filters['compare'] === 'meta_data') {
            return $this->compareMetaData($duplicate);
        } else {
            return $this->compareFullTexts($duplicate);
        }
    }

    protected function passesThresholdFilter($duplicate): bool
    {
        return $duplicate['similarity'] >= $this->filters['threshold'];
    }

    protected function compareMetaData($duplicate): bool
    {
        return isset($duplicate['similarity']) && $duplicate['similarity'] >= $this->filters['threshold'];
    }

    protected function compareFullTexts($duplicate): bool
    {
        return isset($duplicate['similarity']) && $duplicate['similarity'] >= $this->filters['threshold'];
    }

    public function render()
    {
        $perPage = $this->filters['perPage'];
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $duplicates = $this->findDuplicates();
        $currentPageDuplicates = $duplicates->forPage($currentPage, $perPage);
        $paginator = new LengthAwarePaginator($currentPageDuplicates, $duplicates->count(), $perPage, $currentPage);

        return view('livewire.duplicates-table', [
            'tableData' => $this->formatTableData($currentPageDuplicates),
            'pagination' => $paginator->links(),
        ]);
    }
}
