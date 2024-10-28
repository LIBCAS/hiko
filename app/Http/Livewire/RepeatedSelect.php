<?php

namespace App\Http\Livewire;

use Livewire\Component;

class RepeatedSelect extends Component
{
    public $items;
    public $fieldLabel;
    public $fieldKey;
    public $route;

    public function addItem()
    {
        $this->items[] = [
            'value' => '',
            'label' => '',
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function changeItemValue($index, $data)
    {
        $this->items[$index]['label'] = $data['label'] ?: '';
        $this->items[$index]['value'] = $data['label'] ? $data['value'] : '';
    }

    public function mount()
    {
        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function render()
    {
        return view('livewire.repeated-select');
    }
}