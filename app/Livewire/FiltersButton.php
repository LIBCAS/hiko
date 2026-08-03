<?php

namespace App\Livewire;

use App\Models\GlobalIdentity;
use App\Models\Identity;
use App\Services\LetterFilterService;
use Livewire\Component;

class FiltersButton extends Component
{
    public bool $isOpen = false;
    public array $activeFilters = []; // To store active filter labels for display

    protected $listeners = ['filterToggled' => 'updateActiveFilters'];

    public function mount()
    {
        $filters = (array) request()->query('filters', []);
        $this->activeFilters = $this->extractActiveFilters(
            app(LetterFilterService::class)->normalize($filters)
        );
    }

    public function toggleFilters()
    {
        $this->isOpen = !$this->isOpen;
    }

    public function updateActiveFilters(array $filters)
    {
        $this->activeFilters = $this->extractActiveFilters($filters);
    }

    private function extractActiveFilters(array $filters): array
    {
        $active = [];
        $identityLabels = $this->identityLabels($filters);

        if (($filters['match'] ?? LetterFilterService::MATCH_ALL) === LetterFilterService::MATCH_ANY) {
            $active['match'] = ['label' => __('hiko.filter_match_mode'), 'value' => __('hiko.match_any_filter')];
        }
        if (isset($filters['id']) && !empty($filters['id'])) {
            $active['id'] = ['label' => __('hiko.id'), 'value' => $filters['id']];
        }
        if (isset($filters['after']) && !empty($filters['after'])) {
            $active['after'] = ['label' => __('hiko.from'), 'value' => $filters['after']];
        }
        if (isset($filters['before']) && !empty($filters['before'])) {
            $active['before'] = ['label' => __('hiko.to'), 'value' => $filters['before']];
        }
        if (isset($filters['signature']) && !empty($filters['signature'])) {
            $active['signature'] = ['label' => __('hiko.signature'), 'value' => $filters['signature']];
        }
        if (isset($filters['author']) && !empty($filters['author'])) {
            $active['author'] = ['label' => __('hiko.author'), 'value' => $identityLabels['author']];
        }
        if (isset($filters['recipient']) && !empty($filters['recipient'])) {
            $active['recipient'] = ['label' => __('hiko.recipient'), 'value' => $identityLabels['recipient']];
        }
        if (isset($filters['origin']) && !empty($filters['origin'])) {
            $active['origin'] = ['label' => __('hiko.origin'), 'value' => $filters['origin']];
        }
        if (isset($filters['destination']) && !empty($filters['destination'])) {
            $active['destination'] = ['label' => __('hiko.destination'), 'value' => $filters['destination']];
        }
        if (isset($filters['repository']) && !empty($filters['repository'])) {
            $active['repository'] = ['label' => __('hiko.repository'), 'value' => $filters['repository']];
        }
        if (isset($filters['archive']) && !empty($filters['archive'])) {
            $active['archive'] = ['label' => __('hiko.archive'), 'value' => $filters['archive']];
        }
        if (isset($filters['collection']) && !empty($filters['collection'])) {
            $active['collection'] = ['label' => __('hiko.collection'), 'value' => $filters['collection']];
        }
        if (isset($filters['keyword']) && !empty($filters['keyword'])) {
            $active['keyword'] = ['label' => __('hiko.keywords'), 'value' => $filters['keyword']];
        }
        if (isset($filters['mentioned']) && !empty($filters['mentioned'])) {
            $active['mentioned'] = ['label' => __('hiko.mentioned'), 'value' => $identityLabels['mentioned']];
        }
        if (isset($filters['content_stripped']) && $filters['content_stripped'] !== '') {
            $active['content_stripped'] = ['label' => __('hiko.full_text'), 'value' => $filters['content_stripped']];
        }
        if (isset($filters['abstract']) && !empty($filters['abstract'])) {
            $active['abstract'] = ['label' => __('hiko.abstract'), 'value' => $filters['abstract']];
        }
        if (isset($filters['languages']) && !empty($filters['languages'])) {
            $active['languages'] = ['label' => __('hiko.language') . ' ' . __('hiko.in_english'), 'value' => $filters['languages']];
        }
        if (isset($filters['notes_private']) && $filters['notes_private'] !== '') {
            $active['notes_private'] = ['label' => __('hiko.notes_private'), 'value' => $filters['notes_private']];
        }
        if (isset($filters['media']) && $filters['media'] !== '') {
            $active['media'] = ['label' => __('hiko.media'), 'value' => $filters['media'] == '1' ? __('hiko.with_media') : __('hiko.without_media')];
        }
        if (isset($filters['status']) && !empty($filters['status'])) {
            $active['status'] = ['label' => __('hiko.status'), 'value' => __("hiko.{$filters['status']}")];
        }
        if (isset($filters['approval']) && $filters['approval'] !== '') {
            $active['approval'] = ['label' => __('hiko.approval'), 'value' => $filters['approval'] == \App\Models\Letter::APPROVED ? __('hiko.approved') : __('hiko.not_approved')];
        }
        if (isset($filters['editor']) && !empty($filters['editor'])) {
            $active['editor'] = ['label' => __('hiko.editors'), 'value' => $filters['editor']];
        }

        return $active;
    }

    protected function identityLabels(array $filters): array
    {
        $references = collect(LetterFilterService::IDENTITY_FILTERS)
            ->flatMap(function ($field) use ($filters) {
                $values = $filters[$field] ?? [];
                return is_array($values) ? $values : [$values];
            })
            ->filter()
            ->unique()
            ->values();

        $localIds = $references
            ->filter(fn ($value) => preg_match('/^local-\d+$/', (string) $value))
            ->map(fn ($value) => (int) substr($value, 6));
        $globalIds = $references
            ->filter(fn ($value) => preg_match('/^global-\d+$/', (string) $value))
            ->map(fn ($value) => (int) substr($value, 7));

        $local = Identity::query()->whereKey($localIds)->pluck('name', 'id');
        $global = GlobalIdentity::query()->whereKey($globalIds)->pluck('name', 'id');

        return collect(LetterFilterService::IDENTITY_FILTERS)->mapWithKeys(function ($field) use ($filters, $local, $global) {
            $values = $filters[$field] ?? [];
            $values = is_array($values) ? $values : [$values];

            $labels = collect($values)->map(function ($value) use ($local, $global) {
                if (preg_match('/^local-(\d+)$/', (string) $value, $matches)) {
                    return $local->get((int) $matches[1], $value) . ' (' . __('hiko.local') . ')';
                }

                if (preg_match('/^global-(\d+)$/', (string) $value, $matches)) {
                    return $global->get((int) $matches[1], $value) . ' (' . __('hiko.global') . ')';
                }

                return $value;
            })->filter()->implode('; ');

            return [$field => $labels];
        })->all();
    }

    public function removeFilter(string $filterKey)
    {
        $this->dispatch('removeFilter', ['filterKey' => $filterKey]);
    }

    public function render()
    {
        return view('livewire.filters-button');
    }
}
