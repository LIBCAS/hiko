<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Media;

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

        $this->attachedImages = Media::where('model_id', $this->letter->id)
            ->where('model_type', \App\Models\Letter::class)
            ->get()
            ->map(function ($media) {
                return array_merge($media->toArray(), [
                    'thumb_url' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : null,
                    'original_url' => $media->getUrl(),
                ]);
            })
            ->toArray();

        $this->loading = false;
    }

    public function edit($id, $formData)
    {
        $image = Media::where('id', $id)->first();

        if ($image) {
            $image->setCustomProperty('description', $formData['description']);
            $image->setCustomProperty('status', $formData['status'] === 'publish' ? 'publish' : 'private');
            $image->save();
        }
    }

    public function reorder($orderedIds)
    {
        collect($orderedIds)->each(function ($id, $index) {
            $image = Media::where('id', $id)->first();
            if ($image) {
                $image->order_column = $index;
                $image->save();
            }
        });

        $this->dispatch('imageChanged');
    }

    public function remove($id)
    {
        Media::where('id', $id)->delete();
        $this->dispatch('imageRemoved');
    }

    public function mount()
    {
        $this->getMedia();
    }

    public function render()
    {
        return view('livewire.image-metadata-form');
    }
}
