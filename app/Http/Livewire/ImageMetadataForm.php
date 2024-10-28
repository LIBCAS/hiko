<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Spatie\MediaLibrary\Models\Media;

class ImageMetadataForm extends Component
{
    public $letter;
    public $attachedImages = [];
    public $loading = false;

    protected $table;

    protected $listeners = [
        'imageAdded' => 'getMedia',
        'imageRemoved' => 'getMedia',
        'imageChanged' => 'getMedia',
    ];

    public function getMedia()
    {
        $tenantPrefix = tenancy()->tenant->table_prefix;

        $this->loading = true;
        $this->attachedImages = Media::from($tenantPrefix . '__media')
            ->where('model_id', $this->letter->id)
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
        $tenantPrefix = tenancy()->tenant->table_prefix;
        // Fetch the image directly from the tenant-specific table without the "media" alias
        $image = Media::from($tenantPrefix . '__media')->where('id', $id)->first();
        
        if ($image) {
            $image->setCustomProperty('description', $formData['description']);
            $image->setCustomProperty('status', $formData['status'] === 'publish' ? 'publish' : 'private');
            $image->save();
        }
    }

    public function reorder($orderedIds)
    {
        $tenantPrefix = tenancy()->tenant->table_prefix;

        collect($orderedIds)->each(function ($id, $index) use ($tenantPrefix) {
            $image = Media::from($tenantPrefix . '__media')->where('id', $id)->first();
            if ($image) {
                $image->order_column = $index;
                $image->save();
            }
        });

        $this->emit('imageChanged');
    }

    public function remove($id)
    {
        $tenantPrefix = tenancy()->tenant->table_prefix;

        Media::from($tenantPrefix . '__media')->where('id', $id)->delete();
        $this->emit('imageRemoved');
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
