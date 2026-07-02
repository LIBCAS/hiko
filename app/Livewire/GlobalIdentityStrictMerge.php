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
    ];

    public array $selectedIds = [];

    public bool $candidateScanComplete = false;
    public array $candidateGroups = [];
    public int $candidatePage = 1;
    public int $candidatePerPage = 50;
    public bool $candidateHasPreviousPage = false;
    public bool $candidateHasNextPage = false;
    public array $candidateCriteria = [];
    public int $candidateNameSimilarityThreshold = 80;
    public int $candidateBirthYearTolerance = 5;
    public int $candidateDeathYearTolerance = 5;
    public array $candidateYearToleranceOptions = [0, 1, 2, 5];

    public ?array $localIdentityPreview = null;

    public function mount(): void
    {
        $config = config('global_identity_strict_merge.similarity_candidate_scan');

        $this->candidateCriteria = $config['default_criteria'] ?? ['name_similarity', 'date_similarity'];
        $this->candidateNameSimilarityThreshold = (int)($config['name_similarity_threshold'] ?? 80);
        $this->candidateBirthYearTolerance = (int)($config['birth_year_tolerance'] ?? 5);
        $this->candidateDeathYearTolerance = (int)($config['death_year_tolerance'] ?? 5);
        $this->candidateYearToleranceOptions = collect($config['year_tolerance_options'] ?? [0, 1, 2, 5])
            ->map(fn($value) => max(0, (int)$value))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

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
        ];
        $this->resetPage('globalIdentitiesPage');
    }

    public function scanCandidates(): void
    {
        $this->candidatePage = 1;
        $this->loadCandidatePage();
    }

    public function previousCandidatePage(): void
    {
        if ($this->candidatePage <= 1) {
            return;
        }

        $this->candidatePage--;
        $this->loadCandidatePage();
    }

    public function nextCandidatePage(): void
    {
        if (!$this->candidateHasNextPage) {
            return;
        }

        $this->candidatePage++;
        $this->loadCandidatePage();
    }

    public function loadCandidatePage(): void
    {
        if (count($this->candidateCriteria) < 1) {
            $this->dispatch('notify', ['type' => 'error', 'message' => __('hiko.at_least_one_criterion_required')]);
            return;
        }

        $result = app(GlobalIdentityStrictMergeService::class)
            ->findSimilarityCandidates($this->candidateCriteria, [
                'name_similarity_threshold' => $this->candidateNameSimilarityThreshold,
                'birth_year_tolerance' => $this->candidateBirthYearTolerance,
                'death_year_tolerance' => $this->candidateDeathYearTolerance,
                'limit' => (int)config('global_identity_strict_merge.similarity_candidate_scan.group_limit', 50),
                'page' => $this->candidatePage,
            ]);

        $this->candidateGroups = $result['groups'] ?? [];
        $this->candidatePage = (int)($result['page'] ?? $this->candidatePage);
        $this->candidatePerPage = (int)($result['per_page'] ?? $this->candidatePerPage);
        $this->candidateHasPreviousPage = (bool)($result['has_previous'] ?? false);
        $this->candidateHasNextPage = (bool)($result['has_next'] ?? false);

        $this->candidateScanComplete = true;
    }

    public function resetCandidateScan(): void
    {
        $this->candidateScanComplete = false;
        $this->candidateGroups = [];
        $this->candidatePage = 1;
        $this->candidateHasPreviousPage = false;
        $this->candidateHasNextPage = false;
    }

    public function previewCandidateGroup(array $ids)
    {
        $this->selectedIds = collect($ids)
            ->map(fn($id) => (int)$id)
            ->filter(fn(int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        return $this->preview();
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
