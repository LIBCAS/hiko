<?php

namespace App\Livewire;

use Livewire\Component;

class SortingForm extends Component
{
    public array $sorting = [
        'order' => 'updated_at',
        'direction' => 'desc',
    ];

    public array $sortingOptions = [];

    public function mount(array $sortingOptions = [])
    {
        // Define all possible sorting options
        $this->sortingOptions = $sortingOptions ?: [
            'id' => __('hiko.id'),
            'date_computed' => __('hiko.by_letter_date'),
            'updated_at' => __('hiko.by_update'),
            'author' => 'author',
            'recipient' => __('hiko.by_recipient'),
            'origin' => __('hiko.by_origin'),
            'destination' => __('hiko.by_destination'),
            'repository' => __('hiko.by_repository'),
            'archive' => __('hiko.by_archive'),
            'collection' => __('hiko.by_collection'),
            'keyword' => __('hiko.by_keywords'),
            'mentioned' => __('hiko.by_mentioned'),
            'fulltext' => __('hiko.by_full_text'),
            'abstract' => __('hiko.by_abstract'),
            'languages' => __('hiko.by_language'),
            'note' => __('hiko.by_note'),
            'media' => __('hiko.by_media'),
            'status' => __('hiko.by_status'),
            'approval' => __('hiko.by_approval'),
            'editor' => __('hiko.by_editors'),
        ];

        // Restore previous sorting from session
        $this->sorting = session()->get('lettersTableSorting', [
            'order' => 'updated_at',
            'direction' => 'desc',
        ]);
    }

    public function updatedSorting()
    {
        $this->applySorting();
    }

    public function applySorting()
    {
        session()->put('lettersTableSorting', $this->sorting);
        $this->dispatch('sortingChanged', sorting: $this->sorting);
    }

    public function resetSorting()
    {
        $this->sorting = [
            'order' => 'updated_at',
            'direction' => 'desc',
        ];
        $this->applySorting();
    }

    public function render()
    {
        return view('livewire.sorting-form');
    }
}
