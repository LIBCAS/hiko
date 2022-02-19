<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Location;

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
            'ms_manifestation' => '',
            'preservation' => '',
            'repository' => '',
            'signature' => '',
            'type' => '',
        ];
    }

    public function removeItem($index)
    {
        unset($this->copies[$index]);
        $this->copies = array_values($this->copies);
    }

    public function mount()
    {
        $this->copyValues = $this->getCopyValues();
        $this->locations = $this->getLocations();

        if (request()->old('copies')) {
            $this->copies = request()->old('copies');
        }

        if (empty($this->copies)) {
            $this->copies = [];
        }
    }

    public function render()
    {
        return view('livewire.letter-copies');
    }

    protected function getCopyValues()
    {
        return [
            'ms_manifestation' => ['E', 'S',  'D', 'ALS', 'O', 'P'],
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

    protected function getLocations()
    {
        return Location::select(['name', 'type'])
            ->get()
            ->groupBy('type')
            ->toArray();
    }
}
