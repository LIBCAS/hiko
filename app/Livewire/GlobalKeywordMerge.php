<?php

namespace App\Livewire;

use App\Services\GlobalKeywordMergeService;
use App\Services\PageLockService;
use Livewire\Component;
use Livewire\WithPagination;

class GlobalKeywordMerge extends Component
{
    use WithPagination;

    public array $filters = [
        'name' => '',
        'strategy' => 'all',
    ];

    public array $criteria = [];
    public int $nameSimilarityThreshold;

    public array $selectedIds = [];
    public bool $selectAll = true;
    public bool $isProcessing = false;

    public function mount(): void
    {
        $this->criteria = config('global_keyword_merge.default_criteria', ['name_similarity']);
        $this->nameSimilarityThreshold = (int)config('global_keyword_merge.name_similarity_threshold', 80);
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

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->updateSelection();
            return;
        }

        $this->selectedIds = [];
    }

    public function execute(): void
    {
        $user = auth()->user();
        if (!$user) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Unauthenticated.']);
            return;
        }

        $lock = app(PageLockService::class)->assertOwned([
            'scope' => 'global',
            'resource_type' => 'keyword_global_merge',
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

        $service = app(GlobalKeywordMergeService::class);
        $result = $service->executeMerge($this->selectedIds, $this->criteria, [
            'name_similarity_threshold' => $this->nameSimilarityThreshold,
        ]);

        if ($result['success']) {
            $message = __('hiko.keywords_merge_success', [
                'merged' => $result['merged'],
                'created' => $result['created'],
            ]);

            if ($result['skipped'] > 0) {
                $message .= ' ' . __('hiko.keywords_merge_skipped', ['skipped' => $result['skipped']]);
            }

            $this->dispatch('notify', ['type' => 'success', 'message' => $message, 'autoClose' => false]);
            $this->resetPage();
            $this->updateSelection();
        } else {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('hiko.keywords_merge_error') . ': ' . ($result['error'] ?? __('hiko.something_went_wrong')),
            ]);
        }

        $this->isProcessing = false;
    }

    public function render()
    {
        $service = app(GlobalKeywordMergeService::class);
        $allData = $service->previewMerges($this->criteria, [
            'name_similarity_threshold' => $this->nameSimilarityThreshold,
        ], $this->filters);

        $page = $this->getPage();
        $perPage = (int)config('global_keyword_merge.preview_per_page', 25);

        $items = $allData->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $allData->count(),
            $perPage,
            $page,
            ['path' => request()->url()]
        );

        return view('livewire.global-keyword-merge', [
            'previewData' => $paginator,
            'totalCount' => $allData->count(),
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

        $service = app(GlobalKeywordMergeService::class);
        $allData = $service->previewMerges($this->criteria, [
            'name_similarity_threshold' => $this->nameSimilarityThreshold,
        ], $this->filters);

        $this->selectedIds = $allData->pluck('local.id')->toArray();
    }
}
