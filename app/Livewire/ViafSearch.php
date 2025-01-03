<?php

namespace App\Livewire;

use App\Services\Viaf;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class ViafSearch extends Component
{
    public string $search = '';
    public string $error = '';
    public Collection $searchResults;

    public function selectIdentity($id)
    {
        $this->search = '';

        $this->emit('identitySelected', [
            'id' => $id,
        ]);
    }

    public function render(): View
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
