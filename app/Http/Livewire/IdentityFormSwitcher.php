<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\GlobalProfession;
use App\Models\GlobalProfessionCategory;

class IdentityFormSwitcher extends Component
{
    public $types;
    public $identityType;
    public $selectedProfessions;
    public $selectedCategories;
    public $identity;

    public function mount($types, $identityType, $identity, $selectedProfessions, $selectedCategories)
    {
        $this->types = $types;
        $this->identityType = $identityType;
        $this->identity = $identity;

        $this->selectedProfessions = GlobalProfession::all()->map(function ($profession) use ($selectedProfessions) {
            return [
                'value' => $profession->id,
                'label' => $profession->name,
                'selected' => in_array($profession->id, $selectedProfessions->pluck('id')->toArray())
            ];
        })->toArray();

        $this->selectedCategories = GlobalProfessionCategory::all()->map(function ($category) use ($selectedCategories) {
            return [
                'value' => $category->id,
                'label' => $category->name,
                'selected' => in_array($category->id, $selectedCategories->pluck('id')->toArray())
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.identity-form-switcher');
    }
}
