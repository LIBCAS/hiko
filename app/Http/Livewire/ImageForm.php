<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ImageForm extends Component
{
    public $letter;

    public function render()
    {
        return view('livewire.image-form');
    }
}
