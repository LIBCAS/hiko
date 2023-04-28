<?php

namespace App\Http\Livewire;

use Livewire\Component;

class Editor extends Component
{
    public $letter;

    public function save($html, $plainText)
    {
        $this->letter->content = $html;
        $this->letter->content_stripped = $plainText;
        $this->letter->save();
        $this->emit('saved');
    }

    public function render()
    {
        return view('livewire.editor');
    }
}
