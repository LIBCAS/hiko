<?php

namespace App\Livewire;

use App\Services\GlobalIdentityStrictMergeService;
use App\Services\PageLockService;
use Livewire\Component;

class GlobalIdentityStrictMergePreview extends Component
{
    public array $ids = [];
    public int $survivorId = 0;
    public array $scalarSelections = [];
    public array $multiSelections = [];
    public bool $isProcessing = false;
    public ?string $typeError = null;

    public function mount(array $ids = []): void
    {
        $this->ids = collect($ids)
            ->map(fn($id) => (int)$id)
            ->filter(fn(int $id) => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->survivorId = (int)($this->ids[0] ?? 0);
        $this->initializeSelections();
    }

    public function initializeSelections(): void
    {
        $service = app(GlobalIdentityStrictMergeService::class);
        $records = $service->getPreviewRecords($this->ids);
        $this->typeError = $records->count() >= 2 && !$service->hasSingleType($records)
            ? __('hiko.strict_global_merge_mixed_types_error')
            : null;

        $this->scalarSelections = $service->survivorScalarDefaults($records, $this->survivorId);
        $this->multiSelections = $service->defaultMultiSelections($records);
    }

    public function updatedSurvivorId($value): void
    {
        $this->survivorId = (int)$value;
        $this->initializeSelections();
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
            'resource_type' => 'identity_strict_global_merge',
        ], $user);

        if (!$lock['ok']) {
            $this->dispatch('notify', ['type' => 'error', 'message' => __('hiko.page_lock_not_owned')]);
            return null;
        }

        $this->isProcessing = true;

        try {
            app(GlobalIdentityStrictMergeService::class)->execute(
                $this->ids,
                $this->survivorId,
                $this->scalarSelections,
                $this->multiSelections
            );

            session()->flash('success', __('hiko.strict_global_merge_success'));

            return redirect()->route('global.identities.edit', $this->survivorId);
        } catch (\Throwable $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('hiko.strict_global_merge_error') . ': ' . $e->getMessage(),
            ]);
        } finally {
            $this->isProcessing = false;
        }

        return null;
    }

    public function render()
    {
        $service = app(GlobalIdentityStrictMergeService::class);
        $records = $service->getPreviewRecords($this->ids);
        $fields = $records->isNotEmpty() && $service->hasSingleType($records)
            ? $service->fieldsForType($records->first()->type)
            : [];

        return view('livewire.global-identity-strict-merge-preview', [
            'records' => $records,
            'fields' => $fields,
            'typeError' => $this->typeError,
        ]);
    }
}
