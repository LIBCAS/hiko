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

    public function addItem()
    {
        $fields = array_merge(['value', 'label'], array_map(fn($field) => $field['key'], $this->fields));

        $newItem = [];
        foreach ($fields as $field) {
            $newItem[$field] = '';
        }

        $this->items[] = $newItem;
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function changeItemValue($index, $data)
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['value'] = $data['value'];
            $this->items[$index]['label'] = $data['label'];
        }
    }

    public function render()
    {
        return view('livewire.letter-meta-field', [
            'items' => $this->items,
            'fields' => $this->fields,
        ]);
    }
}
