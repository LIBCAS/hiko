<?php

namespace App\Livewire;

use App\Services\{LetterFilterDefinitions, LetterFilterOptions, LetterFilterService};
use Livewire\Attributes\Url;
use Livewire\Component;

class FiltersForm extends Component
{
    #[Url(history: true, except: ['match' => LetterFilterService::MATCH_ALL])]
    public array $filters = ['match' => LetterFilterService::MATCH_ALL];
    public array $draft = [];
    public int $revision = 0;

    protected $listeners = [
        'removeFilter',
        'edit-letter-filters' => 'openFilters',
        'cancel-letter-filters' => 'cancel'
    ];

    public function mount(): void
    {
        $this->filters = app(LetterFilterService::class)->normalize($this->filters);
        $this->beginEditing();
    }

    public function beginEditing(): void
    {
        $this->fillDraft($this->filters);
    }

    public function openFilters(): void
    {
        $this->beginEditing();
        $this->dispatch('letter-filters-ready');
    }

    private function fillDraft(array $filters): void
    {
        $this->resetValidation();
        $this->draft = ['match' => $filters['match']];
        foreach (LetterFilterDefinitions::fields() as $field => [$type]) {
            $value = $filters[$field] ?? null;
            $this->draft[$field] = match ($type) {
                'text' => is_array($value) ? $value + ['value' => ''] : ['operator' => 'contains', 'value' => $value ?? ''],
                'part', 'count' => $value ?? ['operator' => 'eq', 'value' => ''],
                'entity', 'languages', 'checks' => $value === null ? [] : (array) $value,
                'date_match' => $value ?? 'overlap',
                default => $value ?? '',
            };

            if (in_array($type, ['entity', 'languages'])) {
                $this->draft["{$field}_match"] = $filters["{$field}_match"] ?? 'any';
            }
        }
        $this->revision++;
    }

    public function cancel(): void
    {
        $this->beginEditing();
    }

    /** The URL and table change only on Apply (or immediate badge removal). */
    public function search(): void
    {
        $rules = ['draft.after' => ['nullable', 'date_format:Y-m-d'], 'draft.before' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:draft.after']];

        // Do not compare an upper bound to an empty lower bound.
        if (empty($this->draft['after'])) {
            array_pop($rules['draft.before']);
        }

        foreach (LetterFilterDefinitions::fields() as $field => [$type]) {
            if (in_array($type, ['part', 'count']) && ($this->draft[$field]['operator'] ?? '') !== 'empty') {
                $rules["draft.{$field}.value"] = ['nullable', 'integer', 'min:' . ($type === 'count' ? 0 : 1)];
                if ($type === 'part') {
                    $rules["draft.{$field}.value"][] = 'max:' . (str_ends_with($field, 'year') ? 9999 : (str_ends_with($field, 'month') ? 12 : 31));
                }
            }
        }

        $this->validate($rules, [], collect(LetterFilterDefinitions::fields())->mapWithKeys(fn ($definition, $field) => ["draft.{$field}.value" => __('hiko.' . $definition[1])])->all());
        $draft = $this->draft;

        // Keep historical substring URLs stable when editing an unrelated field.
        if (isset($this->filters['languages']) && is_string($this->filters['languages'])
            && ($draft['languages'] ?? []) === [$this->filters['languages']]) {
            $draft['languages'] = $this->filters['languages'];
        }

        $this->filters = app(LetterFilterService::class)->normalize($draft);
        $this->publishFilters();
        $this->dispatch('letter-filters-applied');
    }

    public function updatedFilters(): void
    {
        // Livewire restores URL-bound properties on browser back/forward navigation.
        $this->filters = app(LetterFilterService::class)->normalize($this->filters);
        $this->beginEditing();
        $this->publishFilters();
    }

    private function publishFilters(): void
    {
        $this->dispatch('filterToggled', filters: $this->filters);
        $this->dispatch('filtersChanged', filters: $this->filters);
    }

    public function resetFilters(): void
    {
        $this->fillDraft(['match' => LetterFilterService::MATCH_ALL]);
    }

    public function removeFilter($key): void
    {
        $key = is_array($key) ? ($key['filterKey'] ?? null) : $key;

        if (is_string($key) && in_array($key, LetterFilterService::allowedFilters(), true)) {
            unset($this->filters[$key], $this->filters["{$key}_match"]);
            $this->filters = app(LetterFilterService::class)->normalize($this->filters);
            $this->beginEditing();
            $this->publishFilters();
        }
    }

    public function updateSelection(string $field, array $values): void
    {
        if (in_array($field, array_merge(LetterFilterService::ENTITY_FILTERS, ['languages']), true)) {
            $this->draft[$field] = array_values(array_filter($values, 'is_string'));
        }
    }

    public function render()
    {
        $options = app(LetterFilterOptions::class);
        $selectedOptions = [];

        foreach (LetterFilterService::ENTITY_FILTERS as $field) {
            $selectedOptions[$field] = $options->options($field, (array) ($this->draft[$field] ?? []));
        }

        return view('livewire.filters-form', [
            'sections' => LetterFilterDefinitions::sections(),
            'selectedOptions' => $selectedOptions,
            'languages' => $options->languages(),
            'activeDraft' => app(LetterFilterService::class)->normalize($this->draft),
        ]);
    }
}
