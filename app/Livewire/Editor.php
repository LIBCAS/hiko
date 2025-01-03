<?php

namespace App\Livewire;

use Livewire\Component;

class Editor extends Component
{
    public $letter;

    public function save($html, $plainText)
    {
        $this->letter->content = $html;
        $this->letter->content_stripped = $plainText;
        $this->letter->save();
        $this->dispatch('saved');
    }

    public function render()
    {
        return view('livewire.editor');
    }
}
