<?php

namespace App\Livewire;

use Livewire\Component;

class FiltersButton extends Component
{
    public bool $isOpen = false;
    public array $activeFilters = []; // To store active filter labels for display

    protected $listeners = ['filterToggled' => 'updateActiveFilters'];

    public function mount()
    {
        // Initialize active filters based on session or default values
        if (session()->has('lettersTableFilters')) {
            $filters = session()->get('lettersTableFilters');
            $this->activeFilters = $this->extractActiveFilters($filters);
        }
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
            $active['author'] = ['label' => __('hiko.author'), 'value' => $filters['author']];
        }
        if (isset($filters['recipient']) && !empty($filters['recipient'])) {
            $active['recipient'] = ['label' => __('hiko.recipient'), 'value' => $filters['recipient']];
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
            $active['mentioned'] = ['label' => __('hiko.mentioned'), 'value' => $filters['mentioned']];
        }
        if (isset($filters['fulltext']) && !empty($filters['fulltext'])) {
            $active['fulltext'] = ['label' => __('hiko.full_text'), 'value' => $filters['fulltext']];
        }
        if (isset($filters['abstract']) && !empty($filters['abstract'])) {
            $active['abstract'] = ['label' => __('hiko.abstract'), 'value' => $filters['abstract']];
        }
        if (isset($filters['languages']) && !empty($filters['languages'])) {
            $active['languages'] = ['label' => __('hiko.language') . ' ' . __('hiko.in_english'), 'value' => $filters['languages']];
        }
        if (isset($filters['note']) && !empty($filters['note'])) {
            $active['note'] = ['label' => __('hiko.note'), 'value' => $filters['note']];
        }
        if (isset($filters['media']) && !empty($filters['media'])) {
            $active['media'] = ['label' => __('hiko.media'), 'value' => $filters['media'] == '1' ? __('hiko.with_media') : __('hiko.without_media')];
        }
        if (isset($filters['status']) && !empty($filters['status'])) {
            $active['status'] = ['label' => __('hiko.status'), 'value' => __("hiko.{$filters['status']}")];
        }
        if (isset($filters['approval']) && !empty($filters['approval'])) {
            $active['approval'] = ['label' => __('hiko.approval'), 'value' => $filters['approval'] == \App\Models\Letter::APPROVED ? __('hiko.approved') : __('hiko.not_approved')];
        }
        if (isset($filters['editor']) && !empty($filters['editor'])) {
            $active['editor'] = ['label' => __('hiko.editors'), 'value' => $filters['editor']];
        }

        return $active;
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
