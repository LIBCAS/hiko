<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\GlobalLocationMergeService;
use App\Services\PageLockService;

class GlobalLocationMerge extends Component
{
    use WithPagination;

    // Filters
    public $filters = [
        'name' => '',
        'type' => 'all',
        'strategy' => 'all', // all, merge, move
    ];

    // Configuration
    public array $criteria;
    public int $nameSimilarityThreshold;

    // Selection
    public $selectedIds = [];
    public $selectAll = true;

    // UI State
    public $isProcessing = false;

    public function mount()
    {
        $this->criteria = config('global_location_merge.default_criteria', ['name_similarity', 'type']);
        $this->nameSimilarityThreshold = config('global_location_merge.name_similarity_threshold', 80);
        $this->updateSelection();
    }

    public function updatedFilters()
    {
        $this->handleDataChange();
    }

    public function updatedCriteria()
    {
        $this->handleDataChange();
    }

    public function updatedNameSimilarityThreshold()
    {
        $this->handleDataChange();
    }

    protected function handleDataChange()
    {
        $this->resetPage();

        if ($this->selectAll) {
            $this->selectedIds = [];
            $this->updateSelection();
        } else {
            $this->selectedIds = [];
        }

        $this->selectedIds = [];
        $this->updateSelection();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->updateSelection();
        } else {
            $this->selectedIds = [];
        }
    }

    public function updated($property)
    {
        if (str_starts_with($property, 'filters.') ||
            $property === 'criteria' ||
            $property === 'nameSimilarityThreshold') {

            $this->resetPage();

            if ($this->selectAll) {
                $this->updateSelection();
            } else {
                $this->selectedIds = [];
            }
        }
    }

    protected function updateSelection()
    {
        if (!$this->selectAll) {
            $this->selectedIds = [];
            return;
        }

        $service = app(GlobalLocationMergeService::class);
        $options = [
            'name_similarity_threshold' => $this->nameSimilarityThreshold
        ];

        $allData = $service->previewMerges($this->criteria, $options, $this->filters);

        $this->selectedIds = $allData->pluck('local.id')->toArray();
    }

    public function execute()
    {
        $user = auth()->user();
        if (!$user) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Unauthenticated.']);
            return;
        }

        $lock = app(PageLockService::class)->assertOwned([
            'scope' => 'global',
            'resource_type' => 'location_global_merge',
        ], $user);

        if (!$lock['ok']) {
            $this->dispatch('notify', ['type' => 'error', 'message' => __('hiko.page_lock_not_owned')]);
            return;
        }

        if (empty($this->selectedIds)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => __('hiko.select_at_least_one')]);
            return;
        }

        $this->isProcessing = true;

        try {
            $service = app(GlobalLocationMergeService::class);

            $options = [
                'name_similarity_threshold' => $this->nameSimilarityThreshold
            ];

            $result = $service->executeMerge($this->selectedIds, $this->criteria, $options);

            if ($result['success']) {
                $msg = __('hiko.locations_merge_success', [
                    'merged' => $result['merged'],
                    'created' => $result['created'],
                ]);
                if ($result['skipped'] > 0) {
                    $msg .= ' ' . __('hiko.locations_merge_skipped', ['skipped' => $result['skipped']]);
                }

                $this->dispatch('notify', ['type' => 'success', 'message' => $msg, 'autoClose' => false]);

                $this->resetPage();
                $this->updateSelection();

            } else {
                $this->dispatch('notify', ['type' => 'error', 'message' => __('hiko.locations_merge_error') . ': ' . $result['error']]);
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => __('hiko.locations_merge_error') . ': ' . $e->getMessage()]);
        }

        $this->isProcessing = false;
    }

    public function render()
    {
        $service = app(GlobalLocationMergeService::class);
        $options = [
            'name_similarity_threshold' => $this->nameSimilarityThreshold
        ];
        $allData = $service->previewMerges($this->criteria, $options, $this->filters);
        $totalCount = $allData->count();
        $confirmationSummary = $this->buildConfirmationSummary($allData);

        $page = $this->getPage();
        $perPage = 25;
        $items = $allData->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items, $totalCount, $perPage, $page,
            ['path' => request()->url()]
        );

        return view('livewire.global-location-merge', [
            'previewData' => $paginator,
            'totalCount' => $totalCount,
            'confirmationItems' => $confirmationSummary['items'],
            'confirmationMergeCount' => $confirmationSummary['merge_count'],
            'confirmationMoveCount' => $confirmationSummary['move_count'],
            'confirmationMoreCount' => $confirmationSummary['more_count'],
        ]);
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
                ? __('hiko.merge') . ' · ' . ($item['reason'] ?? __('hiko.exact_match'))
                : __('hiko.move');

            return [
                'local' => (string) $local->id,
                'local_url' => route('locations.edit', $local->id),
                'method' => $method,
                'result' => $global ? (string) $global->id : '—',
                'result_url' => $global ? route('global.locations.edit', $global->id) : null,
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
