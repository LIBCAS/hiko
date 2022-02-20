<?php

namespace App\Http\Livewire;

use Livewire\Component;

class LetterMetaField extends Component
{
    public $items;
    public $fieldKey;
    public $fields;
    public $route;
    public $label;

    public function addItem()
    {
        $fields = array_merge(['value', 'label'], collect($this->fields)->map(function ($field) {
            return $field['key'];
        })->toArray());

        $newItem = [];

        foreach ($fields as $field) {
            $newItem[$field] = '';
        }

        $this->items[] = $newItem;

        $this->emit('itemChanged');
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->emit('itemChanged');
    }

    public function changeItemValue($index, $data)
    {
        $this->items[$index]['label'] = $data['label'] ? $data['label'] : '';
        $this->items[$index]['value'] = $data['label'] ? $data['value'] : '';
    }

    public function mount()
    {
        if (empty($this->items)) {
            $this->items = [];
        }
    }

    public function render()
    {
        return view('livewire.letter-meta-field');
    }
}
