<?php

namespace App\Http\Livewire;

use Livewire\Component;

class RelatedNames extends Component
{
    public $related_names;

    public function addItem()
    {
        // Ensure $related_names is an array before attempting to add an item
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
        // Initialize $related_names with old input or set it to an empty array
        $this->related_names = request()->old('related_names', []);

        // Ensure $related_names is an array even if it's empty
        if (!is_array($this->related_names)) {
            $this->related_names = [];
        }
    }

    public function render()
    {
        return view('livewire.related-names');
    }
}
