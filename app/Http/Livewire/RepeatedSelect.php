<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\GlobalProfession;
use App\Models\GlobalProfessionCategory;

class RepeatedSelect extends Component
{
    public $items;
    public $fieldLabel;
    public $fieldKey;
    public $route;

    public function mount()
    {
        if (empty($this->items)) {

            if ($this->fieldKey === 'profession') {
                $this->items = GlobalProfession::all()->map(function ($profession) {
                    return [
                        'value' => $profession->id,
                        'label' => $profession->name,
                    ];
                })->toArray();
            }

            if ($this->fieldKey === 'category') {
                $this->items = GlobalProfessionCategory::all()->map(function ($category) {
                    return [
                        'value' => $category->id,
                        'label' => $category->name,
                    ];
                })->toArray();
            }

            if (empty($this->items)) {
                $this->addItem();
            }
        }
    }

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

    public function render()
    {
        return view('livewire.repeated-select');
    }
}
