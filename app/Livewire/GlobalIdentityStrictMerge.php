<?php

namespace App\Livewire;

use App\Services\GlobalIdentityStrictMergeService;
use Livewire\Component;
use Livewire\WithPagination;

class GlobalIdentityStrictMerge extends Component
{
    use WithPagination;

    public array $filters = [
        'ids' => '',
        'name' => '',
        'type' => 'all',
        'admin_notes' => '',
        'duplicates_only' => false,
    ];

    public array $selectedIds = [];

    public ?array $localIdentityPreview = null;

    public function updatedFilters(): void
    {
        $this->resetPage('globalIdentitiesPage');
    }

    public function resetFilters(): void
    {
        $this->filters = [
            'ids' => '',
            'name' => '',
            'type' => 'all',
            'admin_notes' => '',
            'duplicates_only' => false,
        ];
        $this->resetPage('globalIdentitiesPage');
    }

    public function toggleDuplicatesOnly(): void
    {
        $this->filters['duplicates_only'] = !($this->filters['duplicates_only'] ?? false);
        $this->resetPage('globalIdentitiesPage');
    }

    public function showLocalIdentityPreview(string $reference): void
    {
        $this->localIdentityPreview = app(GlobalIdentityStrictMergeService::class)
            ->getLocalIdentityPreview($reference);

        if ($this->localIdentityPreview === null) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('hiko.local_identity_preview_unavailable'),
            ]);
        }
    }

    public function closeLocalIdentityPreview(): void
    {
        $this->localIdentityPreview = null;
    }

    public function preview()
    {
        $ids = collect($this->selectedIds)
            ->map(fn($id) => (int)$id)
            ->filter(fn(int $id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->count() < 2) {
            $this->dispatch('notify', ['type' => 'error', 'message' => __('hiko.strict_global_merge_select_at_least_two')]);
            return null;
        }

        $records = app(GlobalIdentityStrictMergeService::class)->getPreviewRecords($ids->all());

        if ($records->count() !== $ids->count() || !app(GlobalIdentityStrictMergeService::class)->hasSingleType($records)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => __('hiko.strict_global_merge_mixed_types_error')]);
            return null;
        }

        return redirect()->route('identities.global-strict-merge.preview', [
            'ids' => $ids->implode(','),
        ]);
    }

    public function render()
    {
        $service = app(GlobalIdentityStrictMergeService::class);
        $identities = $service
            ->getSelectionQuery($this->filters)
            ->paginate(100, ['*'], 'globalIdentitiesPage');

        return view('livewire.global-identity-strict-merge', [
            'identities' => $identities,
        ]);
    }

    public function formatRelatedNamesList(mixed $relatedNames): string
    {
        $relatedNames = is_array($relatedNames) ? $relatedNames : json_decode((string)$relatedNames, true);

        if (!is_array($relatedNames) || empty($relatedNames)) {
            return '—';
        }

        $items = collect($relatedNames)
            ->filter(fn($name) => is_array($name))
            ->map(function (array $name): string {
                $parts = [
                    $name['surname'] ?? '',
                    $name['forename'] ?? '',
                    $name['general_name_modifier'] ?? '',
                ];

                return trim(implode(' ', array_filter($parts, fn($part) => trim((string)$part) !== '')));
            })
            ->filter()
            ->map(fn(string $name): string => '<li>' . e($name) . '</li>')
            ->implode('');

        return $items !== '' ? '<ul class="list-disc list-inside text-gray-600 space-y-1">' . $items . '</ul>' : '—';
    }

    public function formatProfessionsList(mixed $identity): string
    {
        if (!$identity->relationLoaded('professions') || $identity->professions->isEmpty()) {
            return '—';
        }

        $locale = app()->getLocale();
        $items = $identity->professions
            ->map(function ($profession) use ($locale): string {
                $name = e($profession->getTranslation('name', $locale));
                $category = $profession->profession_category;
                $categoryName = $category
                    ? e($category->getTranslation('name', $locale))
                    : e(__('hiko.no_attached_category'));
                $professionUrl = route('global.professions.edit', $profession->id);
                $categoryHtml = $category
                    ? ' | <a href="' . route('global.professions.category.edit', $category->id) . '" class="text-xs text-primary-dark border-b border-primary-light hover:border-primary-dark">' . $categoryName . '</a>'
                    : ' | <span class="text-xs text-gray-500">' . $categoryName . '</span>';

                return '<li><a href="' . $professionUrl . '" class="text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark">' . $name . '</a>' . $categoryHtml . '</li>';
            })
            ->implode('');

        return '<ul class="list-disc list-inside text-gray-600 space-y-1">' . $items . '</ul>';
    }

    public function formatAdminNotes(string|null $adminNotes): string
    {
        $service = app(GlobalIdentityStrictMergeService::class);
        return $service->formatAdminNotes($adminNotes);
    }

    public function adminNoteReferences(string|null $adminNotes): array
    {
        return app(GlobalIdentityStrictMergeService::class)->adminNoteReferences($adminNotes);
    }
}
