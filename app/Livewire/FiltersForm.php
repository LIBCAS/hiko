<?php

namespace App\Livewire;

use App\Models\GlobalIdentity;
use App\Models\Identity;
use App\Services\LetterFilterService;
use Livewire\Attributes\Url;
use Livewire\Component;

class FiltersForm extends Component
{
    #[Url(history: true, except: ['match' => LetterFilterService::MATCH_ALL])]
    public array $filters = ['match' => LetterFilterService::MATCH_ALL];

    protected $listeners = ['removeFilter'];

    public function mount()
    {
        // A URL is authoritative and shareable; the session remains a convenient
        // fallback when opening /letters without an explicit filter specification.
        if (!request()->has('filters') && session()->has('lettersTableFilters')) {
            $this->filters = session()->get('lettersTableFilters');
        }

        $this->filters = app(LetterFilterService::class)->normalize($this->filters);
        session()->put('lettersTableFilters', $this->filters);
    }

    public function updatedFilters()
    {
        $this->search();
    }

    public function search()
    {
        $this->filters = app(LetterFilterService::class)->normalize($this->filters);
        $this->dispatch('resetLettersTablePage');
        $this->dispatch('filterToggled', filters: $this->filters);

        session()->put('lettersTableFilters', $this->filters);
        $this->dispatch('filtersChanged', filters: $this->filters);
    }

    public function resetFilters()
    {
        $this->filters = ['match' => LetterFilterService::MATCH_ALL];
        $this->search();
    }

    public function removeFilter($key): void
    {
        if (is_array($key)) {
            $key = $key['filterKey'] ?? null;
        }

        if (is_string($key) && in_array($key, LetterFilterService::ALLOWED_FILTERS, true)) {
            unset($this->filters[$key]);
            $this->search();
        }
    }

    public function updateIdentityFilter(string $field, array $values): void
    {
        if (!in_array($field, LetterFilterService::IDENTITY_FILTERS, true)) {
            return;
        }

        $this->filters[$field] = $values;
        $this->search();
    }

    public function render()
    {
        return view('livewire.filters-form', [
            'identityOptions' => collect(LetterFilterService::IDENTITY_FILTERS)
                ->mapWithKeys(fn ($field) => [$field => $this->selectedIdentityOptions($field)])
                ->all(),
        ]);
    }

    protected function selectedIdentityOptions(string $field): array
    {
        $values = $this->filters[$field] ?? [];
        $values = is_array($values) ? $values : [$values];

        $localIds = [];
        $globalIds = [];
        foreach ($values as $value) {
            if (preg_match('/^local-(\d+)$/', (string) $value, $matches)) {
                $localIds[] = (int) $matches[1];
            } elseif (preg_match('/^global-(\d+)$/', (string) $value, $matches)) {
                $globalIds[] = (int) $matches[1];
            }
        }

        $local = Identity::query()->whereKey($localIds)->pluck('name', 'id');
        $global = GlobalIdentity::query()->whereKey($globalIds)->pluck('name', 'id');

        return collect($values)->map(function ($value) use ($local, $global) {
            if (preg_match('/^local-(\d+)$/', (string) $value, $matches)) {
                $name = $local->get((int) $matches[1], $value);
                return ['value' => $value, 'label' => $name . ' (' . __('hiko.local') . ')'];
            }

            if (preg_match('/^global-(\d+)$/', (string) $value, $matches)) {
                $name = $global->get((int) $matches[1], $value);
                return ['value' => $value, 'label' => $name . ' (' . __('hiko.global') . ')'];
            }

            return ['value' => $value, 'label' => $value];
        })->values()->all();
    }
}
