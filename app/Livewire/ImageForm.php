<?php

namespace App\Livewire;

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

        collect($this->images)->each(function ($image) {
           if (function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant) {
                 try {
                    \Log::info('Starting media upload', ['file' => $image->getRealPath()]);
                    $media =  $this->letter->addMedia($image->getRealPath())
                           ->usingFileName(Str::uuid() . '.' . pathinfo($image->getFilename())['extension'])
                           ->withCustomProperties(['status' => 'private'])
                            ->toMediaCollection('default');
                    \Log::info('Media uploaded', ['media_id' => $media->id, 'file_name' =>  $media->file_name]);
                   } catch (\Exception $e) {
                      \Log::error('Error processing media:', ['exception' => $e]);
                        session()->flash('error', 'Error processing the media upload: ' . $e->getMessage());
                  }
            } else {
                \Log::error('Tenancy not initialized for media upload');
                session()->flash('error', 'Tenancy is not initialized for media upload.');
            }
         });


        $this->images = [];

        $this->dispatch('remove-images');
        $this->dispatch('imageAdded');
    }

    public function render()
    {
        return view('livewire.image-form');
    }
}
