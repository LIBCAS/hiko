<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Letter;
use App\Models\GlobalLocation;
use App\Models\Location;

/**
 * A Livewire component that manages the "copies" array within a Letter record.
 * This component can be used in the Blade form to display existing copies and
 * allow adding/removing them dynamically.
 */
class LetterCopies extends Component
{
    public $copies;
    public $copyValues;
    public $locations;

    public function addItem()
    {
        $this->copies[] = [
            'archive' => '',
            'collection' => '',
            'copy' => '',
            'l_number' => '',
            'location_note' => '',
            'manifestation_notes' => '',
            'preservation' => '',
            'repository' => '',
            'signature' => '',
            'type' => '',
        ];
    }

    /**
     * Initialize the component.
     * If a Letter is provided, we load the "copies" field from it (already cast to array).
     */
    public function mount(Letter $letter = null)
    {
        // Retrieve predefined sets of data (select lists, etc.)
        $this->copyValues = $this->getCopyValues();
        $this->locations = $this->getLocations();

        // Prefer flashed old input so validation errors keep user-entered rows.
        if (session()->hasOldInput()) {
            $this->copies = request()->old('copies', []);
        }
        // If an existing Letter was passed in, load its stored manifestations.
        elseif ($letter && $letter->exists) {
            $this->copies = $letter->copies ?? [];
        }
        // Create scenario with no old input.
        else {
            $this->copies = [];
        }

        // Ensure it's always an array
        if (!is_array($this->copies)) {
            $this->copies = [];
        }

        $this->copies = $this->normalizeCopyLocationSelections($this->copies);
    }

    /**
     * Remove an item from the copies array by index.
     */
    public function removeItem($index)
    {
        unset($this->copies[$index]);
        $this->copies = array_values($this->copies); // reindex
    }

    /**
     * Render the Livewire component, returning the "letter-copies" Blade view.
     */
    public function render()
    {
        return view('livewire.letter-copies');
    }

    protected function getCopyValues()
    {
        return [
            'type' => [
                'vccard',
                'greeting card',
                'invitation card',
                'letter',
                'picture postcard',
                'postcard',
                'telegram',
                'type_other'
            ],
            'preservation' => [
                'carbon copy',
                'copy',
                'draft',
                'original',
                'photocopy',
                'digitalcopy',
                'extract',
                'printed',
                'other',
            ],
            'copy' => [
                'handwritten',
                'typewritten',
                'mode_printed',
                'mode_other'
            ],
        ];
    }

    protected function getLocations()
    {
        return Location::select(['name', 'type'])
            ->get()
            ->groupBy('type')
            ->toArray();
    }

    protected function normalizeCopyLocationSelections(array $copies): array
    {
        return array_map(function ($copy) {
            if (!is_array($copy)) {
                return $copy;
            }

            foreach (['repository', 'archive', 'collection'] as $field) {
                $copy[$field] = $this->normalizeLocationSelection($copy[$field] ?? null);
            }

            return $copy;
        }, $copies);
    }

    protected function normalizeLocationSelection(mixed $selection): mixed
    {
        if (is_array($selection)) {
            $value = $selection['value'] ?? null;

            if (empty($value)) {
                return $selection;
            }

            if (!empty($selection['label'])) {
                return $selection;
            }

            return [
                'value' => $value,
                'label' => $this->resolveLocationLabel($value),
            ];
        }

        if (!is_string($selection) || $selection === '') {
            return $selection;
        }

        if (!preg_match('/^(local|global)-\d+$/', $selection)) {
            return $selection;
        }

        return [
            'value' => $selection,
            'label' => $this->resolveLocationLabel($selection),
        ];
    }

    protected function resolveLocationLabel(string $selection): string
    {
        if (!preg_match('/^(local|global)-(\d+)$/', $selection, $matches)) {
            return $selection;
        }

        $scope = $matches[1];
        $id = (int) $matches[2];

        $location = $scope === 'global'
            ? GlobalLocation::find($id)
            : Location::find($id);

        if (!$location) {
            return $selection;
        }

        return $location->name . ' (' . __('hiko.' . $scope) . ')';
    }
}
