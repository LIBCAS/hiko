<?php

namespace App\Livewire;

use App\Services\GlobalPlaceMergeService;
use App\Services\PageLockService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class GlobalPlaceMerge extends Component
{
    use WithPagination;

    public array $filters = [
        'name' => '',
        'country' => '',
        'strategy' => 'all',
        'reason' => 'all',
    ];

    public array $criteria = [];
    public int $nameSimilarityThreshold;
    public float $latitudeTolerance;
    public float $longitudeTolerance;
    public int $countryAndNameThreshold;

    public array $selectedIds = [];
    public bool $selectAll = true;
    public bool $isProcessing = false;

    public array $mergeAttrs = [];

    public function mount(): void
    {
        $this->criteria = config('global_place_merge.default_criteria', [
            'geoname_id',
            'alternative_names',
            'country_and_name',
            'name_similarity',
            'coordinates',
        ]);
        $this->nameSimilarityThreshold = (int) config('global_place_merge.name_similarity_threshold', 80);
        $this->latitudeTolerance = (float) config('global_place_merge.latitude_tolerance', 0.1);
        $this->longitudeTolerance = (float) config('global_place_merge.longitude_tolerance', 0.1);
        $this->countryAndNameThreshold = (int) config('global_place_merge.country_and_name_threshold', 80);

        $this->updateSelection();
    }

    public function updatedFilters(): void
    {
        $this->handleDataChange();
    }

    public function updatedCriteria(): void
    {
        $this->handleDataChange();
    }

    public function updatedNameSimilarityThreshold(): void
    {
        $this->handleDataChange();
    }

    public function updatedLatitudeTolerance(): void
    {
        $this->handleDataChange();
    }

    public function updatedLongitudeTolerance(): void
    {
        $this->handleDataChange();
    }

    public function updatedCountryAndNameThreshold(): void
    {
        $this->handleDataChange();
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->updateSelection();
            return;
        }

        $this->selectedIds = [];
    }

    public function execute()
    {
        $user = auth()->user();
        if (!$user) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Unauthenticated.']);
            return null;
        }

        $lock = app(PageLockService::class)->assertOwned([
            'scope' => 'global',
            'resource_type' => 'place_global_merge',
        ], $user);

        if (!$lock['ok']) {
            $this->dispatch('notify', ['type' => 'error', 'message' => __('hiko.page_lock_not_owned')]);
            return null;
        }

        if (empty($this->selectedIds)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => __('hiko.select_at_least_one')]);
            return null;
        }

        $this->isProcessing = true;

        try {
            $service = app(GlobalPlaceMergeService::class);
            $processedIds = $this->selectedIds;
            $result = $service->executeMerge(
                $processedIds,
                $this->criteria,
                $this->buildOptions(),
                collect($this->mergeAttrs)->only($processedIds)->toArray()
            );

            if ($result['success']) {
                $message = __('hiko.places_merge_success', [
                    'merged' => $result['merged'],
                    'created' => $result['created'],
                ]);

                if (($result['skipped'] ?? 0) > 0) {
                    $message .= ' ' . __('hiko.places_merge_skipped', ['skipped' => $result['skipped']]);
                }

                $this->dispatch('notify', ['type' => 'success', 'message' => $message, 'autoClose' => false]);

                foreach ($processedIds as $processedId) {
                    unset($this->mergeAttrs[(int) $processedId]);
                }

                $this->resetPage();
                $this->updateSelection();

                return null;
            }

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('hiko.places_merge_error') . ': ' . ($result['error'] ?? __('hiko.something_went_wrong')),
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('hiko.places_merge_error') . ': ' . $e->getMessage(),
            ]);
        } finally {
            $this->isProcessing = false;
        }

        return null;
    }

    public function render()
    {
        $allData = $this->getPreviewData();

        foreach ($allData as $item) {
            $this->initializeMergeAttrs($item);
        }

        $confirmationSummary = $this->buildConfirmationSummary($allData);

        $page = $this->getPage();
        $perPage = (int) config('global_place_merge.preview_per_page', 25);
        $items = $allData->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $allData->count(),
            $perPage,
            $page,
            ['path' => request()->url()]
        );

        return view('livewire.global-place-merge', [
            'previewData' => $paginator,
            'totalCount' => $allData->count(),
            'confirmationItems' => $confirmationSummary['items'],
            'confirmationMergeCount' => $confirmationSummary['merge_count'],
            'confirmationMoveCount' => $confirmationSummary['move_count'],
            'confirmationMoreCount' => $confirmationSummary['more_count'],
        ]);
    }

    private function handleDataChange(): void
    {
        $this->resetPage();

        if ($this->selectAll) {
            $this->updateSelection();
            return;
        }

        $this->selectedIds = [];
    }

    private function updateSelection(): void
    {
        if (!$this->selectAll) {
            $this->selectedIds = [];
            return;
        }

        $allData = $this->getPreviewData();
        $this->selectedIds = $allData->pluck('local.id')->map(fn ($id) => (int) $id)->toArray();

        foreach ($allData as $item) {
            $this->initializeMergeAttrs($item);
        }
    }

    private function getPreviewData()
    {
        if (empty($this->criteria)) {
            return collect();
        }

        return app(GlobalPlaceMergeService::class)->previewMerges(
            $this->criteria,
            $this->buildOptions(),
            $this->filters
        );
    }

    private function buildOptions(): array
    {
        return [
            'name_similarity_threshold' => $this->nameSimilarityThreshold,
            'latitude_tolerance' => $this->latitudeTolerance,
            'longitude_tolerance' => $this->longitudeTolerance,
            'country_and_name_threshold' => $this->countryAndNameThreshold,
        ];
    }

    private function initializeMergeAttrs(array $item): void
    {
        $localId = (int) $item['local']->id;

        if (isset($this->mergeAttrs[$localId])) {
            return;
        }

        $defaultSource = $item['strategy'] === 'merge' && !empty($item['global']) ? 'global' : 'local';

        $this->mergeAttrs[$localId] = [
            'name' => $defaultSource,
            'country' => $defaultSource,
            'division' => $defaultSource,
            'latitude' => $defaultSource,
            'longitude' => $defaultSource,
            'geoname_id' => $defaultSource,
        ];
    }

    private function buildConfirmationSummary($allData): array
    {
        $summaryLimit = (int) config('merge_confirmation.summary_limit', 20);
        $selectedIds = collect($this->selectedIds)->map(fn ($id) => (int) $id)->all();
        $selected = $allData
            ->filter(fn (array $item): bool => in_array((int) $item['local']->id, $selectedIds, true))
            ->values();

        $items = $selected->take($summaryLimit)->map(function (array $item): array {
            $local = $item['local'];
            $global = $item['global'];

            $method = $item['strategy'] === 'merge'
                ? __('hiko.merge') . ' · ' . __('hiko.merge_reason_' . $item['reason'])
                : __('hiko.move');

            return [
                'local' => (string) $local->id,
                'local_url' => route('places.edit', $local->id),
                'method' => $method,
                'result' => $global ? (string) $global->id : '—',
                'result_url' => $global ? route('global.places.edit', $global->id) : null,
            ];
        })->toArray();

        $moveCount = $selected->filter(fn (array $item): bool => ($item['strategy'] ?? null) === 'move')->count();

        return [
            'items' => $items,
            'merge_count' => $selected->count() - $moveCount,
            'move_count' => $moveCount,
            'more_count' => max(0, $selected->count() - count($items)),
        ];
    }

}
