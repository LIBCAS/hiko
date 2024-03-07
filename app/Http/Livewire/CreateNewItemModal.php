<?php

namespace App\Http\Livewire;

use Livewire\Component;

class CreateNewItemModal extends Component
{
    public $showModal = false;
    public $route;
    public $text;

    public function mount($route, $text)
    {
        $this->route = $route;
        $this->text = $text;
    }

    public function openModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.create-new-item-modal');
    }
}
