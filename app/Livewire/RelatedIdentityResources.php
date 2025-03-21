<?php

namespace App\Livewire;

use Livewire\Component;

class RelatedIdentityResources extends Component
{
    public $resources;

    public function addItem()
    {
        $this->resources[] = [
            'title' => '',
            'link' => '',
        ];
    }

    public function removeItem($index)
    {
        unset($this->resources[$index]);
        $this->resources = array_values($this->resources);
    }

    public function mount()
    {
        if (request()->old('related_identity_resources')) {
            $this->resources = request()->old('related_identity_resources');
        }

        if (empty($this->resources)) {
            $this->resources = [];
        }
    }

    public function render()
    {
        return view('livewire.related-identity-resources');
    }
}
