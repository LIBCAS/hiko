<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class GeonamesSearch extends Component
{
    public $search = '';
    public $searchResults = [];

    public function render()
    {
        if (strlen($this->search) >= 2) {
            $this->searchResults = Http::get(route('ajax.geonames'), [
                'search' => $this->search,
            ]);
        }

        return view('livewire.geonames-search');
    }
}
