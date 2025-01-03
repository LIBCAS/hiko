<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class RepeatedSelect extends Component
{
    public $items = [];
    public $fieldLabel;
    public $fieldKey;
    public $route;

    public function mount($items = [], $fieldLabel, $fieldKey, $route)
    {
        $this->items = $items;
        $this->fieldLabel = $fieldLabel;
        $this->fieldKey = $fieldKey;
        $this->route = $route;

        if (empty($this->items)) {
            $this->addItem();
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

    public function updatedItems($searchValue, $index)
    {
        $searchResults = $this->fetchOptions($searchValue);
        $this->items[$index]['options'] = $searchResults;
    }

    private function fetchOptions($search)
    {
        if (empty($search)) {
            return [];
        }

        try {
            $response = Http::get(route($this->route), ['search' => $search]);
            return $response->json() ?? [];
        } catch (\Exception $e) {
            return [];
        }
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
