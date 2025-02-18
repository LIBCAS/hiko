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
