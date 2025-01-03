<?php

namespace App\Livewire;

use Livewire\Component;

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
    public function mount()
    {
        // Initialize from old request data if available
        $this->resources = request()->old('related_resources', []);

        // It's always an array
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
