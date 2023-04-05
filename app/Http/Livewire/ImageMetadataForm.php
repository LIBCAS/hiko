<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ImageMetadataForm extends Component
{
    public $letter;
    public $attachedImages = [];
    public $loading = false;
    protected $listeners = [
        'imageAdded' => 'getMedia',
        'imageRemoved' => 'getMedia',
        'imageChanged' => 'getMedia',
    ];

    public function getMedia()
    {
        $this->loading = true;
        $this->attachedImages = $this->letter->getMedia();
        $this->loading = false;
    }

    public function edit($id, $formData)
    {
        $image = collect($this->attachedImages)->where('id', '=', $id)->first();
        $image->setCustomProperty('description', $formData['description']);
        $image->setCustomProperty('status', $formData['status'] === 'publish' ? 'publish' : 'private');
        $image->save();
    }

    public function reorder($orderedIds)
    {
        collect($orderedIds)->each(function ($id, $index) {
            $image = collect($this->attachedImages)->where('id', '=', $id)->first();
            $image->order_column = $index;
            $image->save();
        });

        $this->emit('imageChanged');
    }

    public function remove($id)
    {
        collect($this->attachedImages)->where('id', '=', $id)->first()->delete();

        $this->emit('imageRemoved');
    }

    public function mount()
    {
        $this->attachedImages = $this->letter->getMedia();
    }

    public function render()
    {
        return view('livewire.image-metadata-form');
    }
}
