<?php

namespace App\Livewire;

use App\Services\GlobalIdentityMergeService;
use App\Services\PageLockService;
use Livewire\Component;
use Livewire\WithPagination;

class GlobalIdentityMerge extends Component
{
    use WithPagination;

    public array $filters = [
        'name' => '',
        'type' => 'all',
    ];

    public array $criteria = [];
    public int $nameSimilarityThreshold;

    public array $selectedIds = [];
    public array $selectedGlobalIds = [];
    public bool $selectAll = true;
    public bool $isProcessing = false;

    public function mount(): void
    {
        $this->criteria = config('global_identity_merge.default_criteria', ['name_similarity', 'type']);
        if (!in_array('type', $this->criteria, true)) {
            $this->criteria[] = 'type';
        }
        $this->nameSimilarityThreshold = (int)config('global_identity_merge.name_similarity_threshold', 80);
        $this->updateSelection();
    }

    public function updatedFilters(): void
    {
        $this->handleDataChange();
    }

    public function updatedCriteria(): void
    {
        if (!in_array('type', $this->criteria, true)) {
            $this->criteria[] = 'type';
        }
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

    public function setSelectedGlobalIdentity(int $localId, $globalId): void
    {
        $globalId = $globalId ? (int)$globalId : null;

        if ($globalId) {
            $this->selectedGlobalIds[$localId] = $globalId;
            return;
        }

        unset($this->selectedGlobalIds[$localId]);
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
            'resource_type' => 'identity_global_merge',
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

        $service = app(GlobalIdentityMergeService::class);
        $result = $service->executeLinks(
            $this->selectedIds,
            $this->selectedGlobalIds,
            $this->criteria,
            ['name_similarity_threshold' => $this->nameSimilarityThreshold]
        );

        if ($result['success']) {
            $message = __('hiko.identities_global_link_success', ['linked' => $result['linked']]);
            if ($result['skipped'] > 0) {
                $message .= ' ' . __('hiko.identities_global_link_skipped', ['skipped' => $result['skipped']]);
            }

            $this->dispatch('notify', ['type' => 'success', 'message' => $message, 'autoClose' => false]);

            $this->resetPage();
            $this->selectedGlobalIds = [];
            $this->updateSelection();
        } else {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('hiko.identities_global_link_error') . ': ' . ($result['error'] ?? __('hiko.something_went_wrong')),
            ]);
        }

        $this->isProcessing = false;
    }

    public function render()
    {
        $service = app(GlobalIdentityMergeService::class);
        $allData = $service->previewLinks($this->criteria, [
            'name_similarity_threshold' => $this->nameSimilarityThreshold,
        ], $this->filters);

        foreach ($allData as $item) {
            $localId = (int)$item['local']->id;
            if (!array_key_exists($localId, $this->selectedGlobalIds) && !empty($item['global'])) {
                $this->selectedGlobalIds[$localId] = (int)$item['global']->id;
            }
        }

        $confirmationSummary = $this->buildConfirmationSummary($allData);

        $page = $this->getPage();
        $perPage = (int)config('global_identity_merge.preview_per_page', 25);

        $items = $allData->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $allData->count(),
            $perPage,
            $page,
            ['path' => request()->url()]
        );

        return view('livewire.global-identity-merge', [
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

        $service = app(GlobalIdentityMergeService::class);
        $allData = $service->previewLinks($this->criteria, [
            'name_similarity_threshold' => $this->nameSimilarityThreshold,
        ], $this->filters);

        $this->selectedIds = $allData->pluck('local.id')->map(fn($id) => (int)$id)->toArray();
    }

    private function buildConfirmationSummary($allData): array
    {
        $selectedIds = collect($this->selectedIds)->map(fn ($id) => (int) $id)->all();
        $selected = $allData
            ->filter(fn (array $item): bool => in_array((int) $item['local']->id, $selectedIds, true))
            ->values();

        $summaryLimit = (int) config('merge_confirmation.summary_limit', 20);

        $items = $selected->take($summaryLimit)->map(function (array $item): array {
            $local = $item['local'];
            $selectedGlobalId = $this->selectedGlobalIds[(int) $local->id] ?? null;
            $selectedGlobal = $selectedGlobalId
                ? $item['global']?->id === (int) $selectedGlobalId
                    ? $item['global']
                    : \App\Models\GlobalIdentity::query()->find((int) $selectedGlobalId)
                : null;

            return [
                'local' => (string) $local->id,
                'local_url' => route('identities.edit', $local->id),
                'method' => __('hiko.link_to'),
                'result' => $selectedGlobal ? (string) $selectedGlobal->id : '—',
                'result_url' => $selectedGlobal ? route('global.identities.edit', $selectedGlobal->id) : null,
            ];
        })->toArray();

        return [
            'items' => $items,
            'merge_count' => $selected->count(),
            'move_count' => 0,
            'more_count' => max(0, $selected->count() - count($items)),
        ];
    }
}
