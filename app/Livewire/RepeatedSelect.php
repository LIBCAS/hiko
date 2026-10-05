<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RepeatedSelect extends Component
{
    public $items = [];
    public $fieldLabel;
    public $fieldKey;
    public $route;
    public $routeParams = [];

    public function mount($items = [], $fieldLabel, $fieldKey, $route, $routeParams = [])
    {
        $this->items = [];
        foreach ($items as $item) {
            $this->items[(string) Str::uuid()] = $item;
        }
        $this->fieldLabel = $fieldLabel;
        $this->fieldKey = $fieldKey;
        $this->route = $route;
        $this->routeParams = $routeParams;

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function addItem()
    {
        $this->items[(string) Str::uuid()] = [
            'value' => '',
            'label' => '',
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
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
            $response = Http::get(route($this->route, $this->routeParams), ['search' => $search]);
            return $response->json() ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function changeItemValue($index, $data)
    {
        if (!array_key_exists($index, $this->items)) {
            return;
        }

        $this->items[$index]['label'] = $data['label'] ?: '';
        $this->items[$index]['value'] = $data['label'] ? $data['value'] : '';
    }

    public function render()
    {
        return view('livewire.repeated-select');
    }
}
