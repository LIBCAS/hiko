<?php

namespace App\Livewire;

use App\Services\Viaf;
use Illuminate\Support\Collection;
use Livewire\Component;

class ViafSearch extends Component
{
    public string $search = '';
    public string $error = '';
    public Collection $searchResults;

    public function selectIdentity($id)
    {
        $this->search = '';

        $this->dispatch('identitySelected', [
            'id' => $id,
        ]);
    }

    public function render()
    {
        $this->searchResults = collect([]);
        $this->error = '';

        if (strlen($this->search) >= 2) {
            try {
                $this->searchResults = (new Viaf)->search($this->search);
            } catch (\Throwable $th) {
                $this->error = $th->getMessage();
            }
        }

        return view('livewire.viaf-search');
    }
}
