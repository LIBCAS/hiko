<?php

namespace App\Http\Livewire;

use Livewire\Component;

class RelatedNames extends Component
{
    public $related_names;

    public function addItem()
    {
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

    public function mount($related_names)
    {
        if (is_string($related_names)) {
            $this->related_names = json_decode($related_names, true);
        } else {
            $this->related_names = $related_names;
        }

        $this->related_names = $this->related_names ?? [];
    }

    public function render()
    {
        return view('livewire.related-names');
    }
}
