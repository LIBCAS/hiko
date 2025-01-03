<?php

namespace App\Livewire;

use Livewire\Component;

class RelatedNames extends Component
{
    public $related_names;

    public function addItem()
    {
        if (!is_array($this->related_names)) {
            $this->related_names = [];
        }

        $this->related_names[] = [
            'surname' => '',
            'forename' => '',
            'general_name_modifier' => '',
        ];
    }

    public function removeItem($index)
    {
        unset($this->related_names[$index]);
        $this->related_names = array_values($this->related_names);
    }

    public function mount()
    {
        if (request()->old('related_names')) {
            $this->related_names = request()->old('related_names');
        }
    
        if (!is_array($this->related_names)) {
            $this->related_names = [];
        }
    }

    public function render()
    {
        return view('livewire.related-names');
    }
}
