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
            'images.required' => __('Nahrajte přílohu'),
            'images.*.required' => __('Nahrajte přílohu'),
            'images.*.mimes' => __('Příloha musí být ve formátu jpg nebo png'),
            'images.*.max' => __('Maximální velikost příloh je 500KB'),
        ], [
            'images' => __('Přílohy'),
            'images.*' => __('Příloha'),
        ]);

        collect($this->images)->each(function ($image) {
            $this->letter->addMedia($image->getRealPath())
                ->usingFileName(Str::uuid() . '.' . pathinfo($image->getFilename())['extension'])
                ->withCustomProperties(['status' => 'private'])
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
