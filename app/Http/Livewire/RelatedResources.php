<?php

namespace App\Http\Livewire;

use Livewire\Component;

class RelatedResources extends Component
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
        if (request()->old('related_resources')) {
            $this->resources = request()->old('related_resources');
        }

        if (empty($this->resources)) {
            $this->resources = [];
        }
    }

    public function render()
    {
        return view('livewire.related-resources');
    }
}
