<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Log;

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
        Log::info('Tenant Table:', [
            'table' => \App\Models\TenantMedia::query()->toSql(),
        ]);
    
        // **Fetch media using the correct relationship**
        $this->attachedImages = $this->letter->media()->get();
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

        $this->dispatch('imageChanged');
    }

    public function remove($id)
    {
        collect($this->attachedImages)->where('id', '=', $id)->first()->delete();

        $this->dispatch('imageRemoved');
    }

    public function mount()
    {
        Log::info('Tenant Table:', [
            'table' => \App\Models\TenantMedia::query()->toSql(),
        ]);
    
        // **Fetch media using the correct relationship**
        $this->attachedImages = $this->letter->media()->get();
    }

    public function render()
    {
        return view('livewire.image-metadata-form');
    }
}
