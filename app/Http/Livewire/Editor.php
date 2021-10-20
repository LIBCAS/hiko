<?php

namespace App\Http\Livewire;

use Livewire\Component;

class Editor extends Component
{
    public $letter;
    public $loading;

    public function save($html)
    {
        $this->loading = false;
        $this->letter->content = $html;
        $this->letter->save();
        $this->loading = true;
    }

    public function render()
    {
        return view('livewire.editor');
    }
}
