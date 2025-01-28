<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Location;
use App\Models\Letter;

/**
 * A Livewire component that manages the "copies" array within a Letter record.
 * This component can be used in the Blade form to display existing copies and
 * allow adding/removing them dynamically.
 */
class LetterCopies extends Component
{
    public $copies;
    public $copyValues;
    public $locations;

    public function addItem()
    {
        $this->copies[] = [
            'archive' => '',
            'collection' => '',
            'copy' => '',
            'l_number' => '',
            'location_note' => '',
            'manifestation_notes' => '',
            'preservation' => '',
            'repository' => '',
            'signature' => '',
            'type' => '',
        ];
    }

    /**
     * Initialize the component.
     * If a Letter is provided, we load the "copies" field from it (already cast to array).
     */
    public function mount(Letter $letter = null)
    {
        // Retrieve predefined sets of data (select lists, etc.)
        $this->copyValues = $this->getCopyValues();
        $this->locations = $this->getLocations();

        // If a Letter was passed in, we assign $this->copies to whatever is stored in the DB.
        // Because in the Letter model we cast "copies" => "array", it should already be an array.
        if ($letter) {
            $this->copies = $letter->copies ?? [];
        }
        // If no letter was passed (create scenario), try old() or default to empty array.
        else {
            $this->copies = request()->old('copies', []);
        }

        // Ensure it's always an array
        if (!is_array($this->copies)) {
            $this->copies = [];
        }
    }

    /**
     * Remove an item from the copies array by index.
     */
    public function removeItem($index)
    {
        unset($this->copies[$index]);
        $this->copies = array_values($this->copies); // reindex
    }

    /**
     * Render the Livewire component, returning the "letter-copies" Blade view.
     */
    public function render()
    {
        return view('livewire.letter-copies');
    }

    protected function getCopyValues()
    {
        return [
            'type' => [
                'vccard',
                'greeting card',
                'invitation card',
                'letter',
                'picture postcard',
                'postcard',
                'telegram',
                'type_other'
            ],
            'preservation' => [
                'carbon copy',
                'copy',
                'draft',
                'original',
                'photocopy',
                'digitalcopy',
                'extract',
                'printed',
                'other',
            ],
            'copy' => [
                'handwritten',
                'typewritten',
                'mode_printed',
                'mode_other'
            ],
        ];
    }

    protected function getLocations()
    {
        return Location::select(['name', 'type'])
            ->get()
            ->groupBy('type')
            ->toArray();
    }
}
