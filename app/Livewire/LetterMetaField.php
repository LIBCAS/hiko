<?php

namespace App\Livewire;

use Livewire\Component;

class LetterMetaField extends Component
{
    public array $items = [];
    public array $fields = [];
    public string $route;
    public string $label;
    public string $fieldKey;

    public function mount($items = [])
    {
        // Ensure each item has an 'id' field
        $this->items = array_map(function ($item) {
            return array_merge(['id' => uniqid()], $item);
        }, $items);
    }

    public function addItem()
    {
        $fields = array_merge(['value', 'label'], array_map(fn($field) => $field['key'], $this->fields));
        $newItem = [];
        foreach ($fields as $field) {
            $newItem[$field] = '';
        }
        $newItem['id'] = uniqid(); // Generate a unique ID for each new item
        $this->items[] = $newItem;

        // Trigger a re-initialization of Alpine.js components
        $this->dispatch('reinitialize-ajax-choices');
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items); // Re-index the array

        // Trigger a re-initialization of Alpine.js components
        $this->dispatch('reinitialize-ajax-choices');
    }

    public function changeItemValue($index, $data)
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['value'] = $data['value'];
            $this->items[$index]['label'] = $data['label'];
        }
    }

    protected $listeners = [
        'item-value-changed' => 'changeItemValue'
    ];

    public function render()
    {
        return view('livewire.letter-meta-field', [
            'items' => $this->items,
            'fields' => $this->fields,
        ]);
    }
}
