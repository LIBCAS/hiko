<?php

namespace App\Livewire;

use Livewire\Component;

class FiltersForm extends Component
{
    public array $filters = [];

    public function mount()
    {
        // Initialize filters from session or default values
        if (session()->has('lettersTableFilters')) {
            $this->filters = session()->get('lettersTableFilters');
        }
    }

    public function updatedFilters()
    {
        $this->search();
    }

    public function search()
    {
        $this->dispatch('resetLettersTablePage');
        $this->dispatch('filterToggled', filters: $this->filters);

        session()->put('lettersTableFilters', $this->filters);
        $this->dispatch('filtersChanged', filters: $this->filters); // Keep the old dispatch
    }

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
    }

    public function render()
    {
        return view('livewire.filters-form');
    }
}
