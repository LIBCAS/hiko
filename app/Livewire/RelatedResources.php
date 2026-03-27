<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Letter;

class RelatedResources extends Component
{
    public $resources = []; // Always init. as an array.

    /**
     * Resource to the list.
     */
    public function addItem()
    {
        $this->resources[] = [
            'title' => '',
            'link' => '',
        ];
    }

    /**
     * No index init.
     *
     * @param int $index
     */
    public function removeItem($index)
    {
        unset($this->resources[$index]);
        $this->resources = array_values($this->resources); // Re-index the array
    }

    /**
     * Resources initialization.
     */
    public function mount(Letter $letter = null)
    {
        if (session()->hasOldInput()) {
            $this->resources = request()->old('related_resources', []);
        } elseif ($letter && $letter->exists) {
            $this->resources = $letter->related_resources ?? [];
        } else {
            $this->resources = [];
        }
    
        if (!is_array($this->resources)) {
            $this->resources = [];
        }
    }    
    /**
     * Component rendering.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.related-resources');
    }
}
