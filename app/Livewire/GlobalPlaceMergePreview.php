<?php

namespace App\Livewire;

use App\Services\GlobalPlaceMergeService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GlobalPlaceMergePreview extends Component
{
    use WithPagination;

    // Criteria from parent form (defaults loaded from config in mount())
    public array $criteria = [];
    public int $nameSimilarityThreshold;
    public float $latitudeTolerance;
    public float $longitudeTolerance;
    public int $countryAndNameThreshold;

    // Filters
    public array $filters = [
        'name' => '',
        'country' => '',
        'strategy' => 'all', // all, merge, move
        'reason' => 'all', // all, geoname_id, alternative_names, etc.
    ];

    // Selection
    public array $selectedPlaces = [];
    public bool $selectAll = true; // Default to checked

    public function mount()
    {
        // Initialize with default criteria from config
        if (empty($this->criteria)) {
            $this->criteria = config('global_place_merge.default_criteria');
        }

        // Ensure thresholds have default values from config
        if (!isset($this->nameSimilarityThreshold) || $this->nameSimilarityThreshold === 0) {
            $this->nameSimilarityThreshold = config('global_place_merge.name_similarity_threshold');
        }
        if (!isset($this->latitudeTolerance) || $this->latitudeTolerance === 0.0) {
            $this->latitudeTolerance = config('global_place_merge.latitude_tolerance');
        }
        if (!isset($this->longitudeTolerance) || $this->longitudeTolerance === 0.0) {
            $this->longitudeTolerance = config('global_place_merge.longitude_tolerance');
        }
        if (!isset($this->countryAndNameThreshold) || $this->countryAndNameThreshold === 0) {
            $this->countryAndNameThreshold = config('global_place_merge.country_and_name_threshold');
        }

        // Select all places by default
        $this->selectAllPlaces();
    }

    public function updatedFilters()
    {
        $this->resetPage('previewPage');

        // Re-select all when filters change
        if ($this->selectAll) {
            $this->selectAllPlaces();
        }
    }

    public function updatedCriteria()
    {
        $this->resetPage('previewPage');

        // Re-select all when criteria change
        if ($this->selectAll) {
            $this->selectAllPlaces();
        }
    }

    public function updatedNameSimilarityThreshold()
    {
        $this->resetPage('previewPage');

        if ($this->selectAll) {
            $this->selectAllPlaces();
        }
    }

    public function updatedLatitudeTolerance()
    {
        $this->resetPage('previewPage');

        if ($this->selectAll) {
            $this->selectAllPlaces();
        }
    }

    public function updatedLongitudeTolerance()
    {
        $this->resetPage('previewPage');

        if ($this->selectAll) {
            $this->selectAllPlaces();
        }
    }

    public function updatedCountryAndNameThreshold()
    {
        $this->resetPage('previewPage');

        if ($this->selectAll) {
            $this->selectAllPlaces();
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectAllPlaces();
        } else {
            $this->selectedPlaces = [];
        }
    }

    protected function selectAllPlaces()
    {
        // Select ALL places across all pages, not just current page
        $allPreviewData = $this->getPreviewData();
        $this->selectedPlaces = $allPreviewData->pluck('local.id')->toArray();
    }

    public function render()
    {
        $previewData = $this->getPreviewData();
        $paginatedData = $this->paginateCollection($previewData);

        return view('livewire.global-place-merge-preview', [
            'previewData' => $paginatedData,
        ]);
    }

    protected function getPreviewData()
    {
        if (empty($this->criteria)) {
            return collect();
        }

        $mergeService = app(GlobalPlaceMergeService::class);

        $options = [
            'name_similarity_threshold' => $this->nameSimilarityThreshold,
            'latitude_tolerance' => $this->latitudeTolerance,
            'longitude_tolerance' => $this->longitudeTolerance,
            'country_and_name_threshold' => $this->countryAndNameThreshold,
        ];

        return $mergeService->previewMerges($this->criteria, $options, $this->filters);
    }

    protected function paginateCollection($collection): LengthAwarePaginator
    {
        $page = $this->getPage('previewPage');
        $perPage = config('global_place_merge.preview_per_page', 25);
        $total = $collection->count();
        $items = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'previewPage']
        );
    }
}
