<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class ImageForm extends Component
{
    use WithFileUploads;

    public $letter;
    public $images = [];

    public function save()
    {
        $this->validate([
            'images' => ['required'],
            'images.*' => ['required', 'mimes:jpeg,jpg,png', 'max:500'],
        ], [
            'images.required' => __('hiko.upload_attachment'),
            'images.*.required' => __('hiko.upload_attachment'),
            'images.*.mimes' => __('hiko.attachment_format_requirement'),
            'images.*.max' => __('hiko.attachment_max_size'),
        ], [
            'images' => __('hiko.attachments'),
            'images.*' => __('hiko.attachment'),
        ]);

        $tenantPrefix = tenancy()->tenant->table_prefix;

        collect($this->images)->each(function ($image) use ($tenantPrefix) {
            $this->letter->addMedia($image->getRealPath())
                ->usingFileName(Str::uuid() . '.' . pathinfo($image->getFilename())['extension'])
                ->withCustomProperties(['status' => 'private'])
                ->withCustomProperties(['table' => $tenantPrefix . '__media'])
                ->toMediaCollection();
        });

        $this->images = [];

        $this->dispatchBrowserEvent('remove-images');

        $this->emit('imageAdded');
    }

    public function render()
    {
        return view('livewire.image-form');
    }
}
