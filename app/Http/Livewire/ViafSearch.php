<?php

namespace App\Http\Livewire;

use App\Services\Viaf;
use Livewire\Component;

class ViafSearch extends Component
{
    public $search = '';
    public $searchResults = [];
    public $error = '';

    public function selectIdentity($id)
    {
        $this->search = '';

        $this->emit('identitySelected', [
            'id' => $id,
        ]);
    }

    public function render()
    {
        if (strlen($this->search) >= 2) {
            try {
                $this->searchResults = (new Viaf)->search($this->search);
                $this->error = '';
            } catch (\Throwable $th) {
                $this->searchResults = [];
                $this->error = $th->getMessage();
            }
        }

        return view('livewire.viaf-search');
    }
}
