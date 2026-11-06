<?php

namespace App\Livewire;

use App\Services\{LetterFilterDefinitions, LetterFilterOptions, LetterFilterService};
use Livewire\Component;

class FiltersButton extends Component
{
    public array $activeFilters = [];
    protected $listeners = ['filterToggled' => 'updateActiveFilters'];

    public function mount(): void
    {
        $this->updateActiveFilters((array) request()->query('filters', []));
    }

    public function updateActiveFilters(array $filters): void
    {
        $filters = app(LetterFilterService::class)->normalize($filters);
        $this->activeFilters = [];
        if ($filters['match'] === 'any') {
            $this->activeFilters['match'] = ['label' => __('hiko.filter_match_mode'), 'value' => __('hiko.match_any_filter')];
        }

        foreach (LetterFilterDefinitions::sections() as $section) {
            foreach ($section['fields'] as $field => [$type, $label]) {
                if (!isset($filters[$field])) {
                    continue;
                }
                $value = $filters[$field];
                if ($type === 'entity') {
                    $value = implode('; ', array_column(app(LetterFilterOptions::class)->options($field, (array) $value), 'label'));
                } elseif ($type === 'checks') {
                    $choices = LetterFilterDefinitions::choices($field);
                    $value = implode('; ', array_map(fn ($v) => $choices[$v] ?? $v, (array) $value));
                } elseif ($type === 'boolean') {
                    $value = __('hiko.' . ((string) $value === '1' ? 'yes' : 'no'));
                } elseif ($type === 'date_match') {
                    $value = __('hiko.filter_within');
                } elseif (is_array($value) && isset($value['operator'])) {
                    $value = __('hiko.filter_' . $value['operator']) . (isset($value['value']) ? ': ' . $value['value'] : '');
                } elseif (is_array($value)) {
                    $value = implode('; ', $value);
                }
                if (($filters["{$field}_match"] ?? '') === 'all') {
                    $value .= ' (' . __('hiko.filter_all_selected') . ')';
                }
                $this->activeFilters[$field] = [
                    'label' => ($type === 'count' || str_ends_with($field, '_marked') ? __('hiko.' . $section['label']) . ' · ' : '') . __('hiko.' . $label),
                    'value' => $value,
                ];
            }
        }
    }

    public function removeFilter(string $filterKey): void
    {
        $this->dispatch('removeFilter', ['filterKey' => $filterKey]);
    }

    public function render()
    {
        return view('livewire.filters-button');
    }
}
