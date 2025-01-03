<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Location;

class LetterCopies extends Component
{
    public $copies = []; // Always initialize as an array
    public $copyValues = [];
    public $locations = [];

    /**
     * Add a new copy item to the list.
     */
    public function addItem()
    {
        $this->copies[] = [
            'archive' => '',
            'collection' => '',
            'copy' => '',
            'l_number' => '',
            'location_note' => '',
            'manifestation_notes' => '',
            'ms_manifestation' => '',
            'preservation' => '',
            'repository' => '',
            'signature' => '',
            'type' => '',
        ];
    }

    /**
     * Remove a copy item by index.
     *
     * @param int $index
     */
    public function removeItem($index)
    {
        unset($this->copies[$index]);
        $this->copies = array_values($this->copies); // Re-index the array
    }

    /**
     * Mount the component and initialize data.
     */
    public function mount()
    {
        $this->copyValues = $this->getCopyValues();
        $this->locations = $this->getLocations();

        // Retrieve old input or initialize as an empty array
        $this->copies = request()->old('copies', []);

        if (!is_array($this->copies)) {
            $this->copies = []; // Ensure it's always an array
        }
    }

    /**
     * Render the Livewire component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.letter-copies');
    }

    /**
     * Get predefined copy values.
     *
     * @return array
     */
    protected function getCopyValues()
    {
        return [
            'ms_manifestation' => ['E', 'S', 'D', 'ALS', 'O', 'P'],
            'type' => [
                'calling card',
                'greeting card',
                'invitation card',
                'letter',
                'picture postcard',
                'postcard',
                'telegram',
                'visiting card',
            ],
            'preservation' => [
                'carbon copy',
                'copy',
                'draft',
                'original',
                'photocopy',
            ],
            'copy' => ['handwritten', 'typewritten'],
        ];
    }

    /**
     * Fetch locations grouped by type.
     *
     * @return array
     */
    protected function getLocations()
    {
        return Location::select(['name', 'type'])
            ->get()
            ->groupBy('type')
            ->map(fn($items) => $items->pluck('name')->toArray())
            ->toArray();
    }
}
