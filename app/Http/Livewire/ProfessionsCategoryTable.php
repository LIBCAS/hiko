<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProfessionCategory;
use App\Models\GlobalProfessionCategory;

class ProfessionsCategoryTable extends Component
{
    use WithPagination;

    public $filters = [
        'source' => 'all', // 'local', 'global', 'all'
        'name' => '',
    ];

    public function search()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
    }

    public function render()
    {
        $categories = $this->findCategories();

        return view('livewire.professions-category-table', [
            'categories' => $categories,
        ]);
    }

    protected function findCategories()
    {
        $filters = $this->filters;
        $categories = collect();

        // Fetch tenant-specific categories
        if ($filters['source'] === 'local' || $filters['source'] === 'all') {
            $tenantCategories = ProfessionCategory::applyFilters($filters)->get();
            $categories = $categories->merge($tenantCategories);
        }

        // Fetch global categories
        if ($filters['source'] === 'global' || $filters['source'] === 'all') {
            $globalCategories = GlobalProfessionCategory::applyFilters($filters)->get();
            $categories = $categories->merge($globalCategories);
        }

        return $categories->paginate(10);
    }
}
