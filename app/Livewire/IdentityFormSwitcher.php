<?php

namespace App\Livewire;

use Livewire\Component;

class IdentityFormSwitcher extends Component
{
    public $identityType;
    public $types;
    public $identity;
    public $selectedProfessions;
    public $selectedCategories;
    public $selectedReligions;
    public $globalMode = false;
    public $professionFieldKey = 'profession';
    public $professionRoute = 'ajax.professions';
    public $professionRouteParams = [];
    public $professionLabel = null;
    public $showReligions = true;
    public $showCreateProfessionModal = true;

    public function render()
    {
        return view('livewire.identity-form-switcher');
    }
}
