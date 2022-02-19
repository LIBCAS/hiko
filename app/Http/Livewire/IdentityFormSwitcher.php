<?php

namespace App\Http\Livewire;

use Livewire\Component;

class IdentityFormSwitcher extends Component
{
    public $identityType;
    public $types;
    public $identity;
    public $selectedProfessions;
    public $selectedCategories;

    public function render()
    {
        return view('livewire.identity-form-switcher');
    }
}
