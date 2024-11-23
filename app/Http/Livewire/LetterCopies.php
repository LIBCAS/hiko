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
            'ms_manifestation' => config('letter_metadata.ms_manifestation'),
            'type' => config('letter_metadata.type'),
            'preservation' => config('letter_metadata.preservation'),
            'copy' => config('letter_metadata.copy'),
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
