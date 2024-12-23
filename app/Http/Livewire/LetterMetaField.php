<?php

namespace App\Http\Livewire;

use Livewire\Component;

class LetterMetaField extends Component
{
    public array $items = [];
    public array $fields = [];
    public string $route;
    public string $label;
    public string $fieldKey;

    public function addItem()
    {
        $fields = array_merge(['value', 'label'], array_map(fn($field) => $field['key'], $this->fields));

        $newItem = [];
        foreach ($fields as $field) {
            $newItem[$field] = '';
        }

        $this->items[] = $newItem;

        $this->dispatch('itemChanged');
    }

    public function removeItem(int $index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->dispatch('itemChanged');
    }

    public function changeItemValue(int $index, array $data)
    {
        $this->items[$index]['label'] = $data['label'] ?? '';
        $this->items[$index]['value'] = !empty($data['label']) ? $data['value'] : '';
    }

    public function mount()
    {
        $this->items = $this->items ?? [];
    }

    public function render()
    {
        return view('livewire.letter-meta-field');
    }

    public function updatedItems()
    {
        $this->dispatch('itemChanged');
    }
}
