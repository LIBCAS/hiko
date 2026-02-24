<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Letter;
use App\Http\Requests\LetterRequest;
use Illuminate\Support\Facades\Validator;
use App\Traits\GeneratesLetterAttributes;

class LettersConsistencyCheck extends Component
{
    use GeneratesLetterAttributes;

    public $isScanning = false;
    public $issues = [];

    public function scan()
    {
        $this->isScanning = true;
        $this->issues = [];

        if (tenancy()->initialized) {
            $rules = (new LetterRequest)->rules();

            Letter::with([
                // Standard relations
                'identities',
                'globalIdentities',
                'localPlaces',
                'globalPlaces',
                'localKeywords',
                'globalKeywords',
                // New relations for integrity check
                'manifestations.repository',
                'manifestations.archive',
                'manifestations.collection',
            ])->chunk(50, function ($letters) use ($rules) {
                foreach ($letters as $letter) {
                    // 1. Standard Validation (Checks scalar fields + Virtual 'copies' array)
                    // This leverages the getCopiesAttribute accessor on the model
                    $data = $this->prepareLetterData($letter);
                    $attributes = $this->generateLetterAttributes($data, $rules);

                    $validator = Validator::make($data, $rules, [], $attributes);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $error) {
                            $this->addIssue($letter, $error);
                        }
                    }

                    // 2. Relational Integrity Check (New Manifestations Table)
                    // Foreign Keys ensure the ID exists, but we must check if the TYPE is correct.
                    foreach ($letter->manifestations as $index => $manifestation) {
                        $position = $index + 1;

                        // Check Repository Type
                        if ($manifestation->repository && $manifestation->repository->type !== 'repository') {
                            $this->addIssue($letter,
                                "Manifestation #{$position}: Linked Repository '{$manifestation->repository->name}' is defined as '{$manifestation->repository->type}' in Locations.");
                        }

                        // Check Archive Type
                        if ($manifestation->archive && $manifestation->archive->type !== 'archive') {
                            $this->addIssue($letter,
                                "Manifestation #{$position}: Linked Archive '{$manifestation->archive->name}' is defined as '{$manifestation->archive->type}' in Locations.");
                        }

                        // Check Collection Type
                        if ($manifestation->collection && $manifestation->collection->type !== 'collection') {
                            $this->addIssue($letter,
                                "Manifestation #{$position}: Linked Collection '{$manifestation->collection->name}' is defined as '{$manifestation->collection->type}' in Locations.");
                        }
                    }
                }
            });
        }

        $this->isScanning = false;
    }

    protected function addIssue(Letter $letter, string $message): void
    {
        $this->issues[] = [
            'id' => $letter->id,
            'uuid' => $letter->uuid,
            'date' => $letter->pretty_date,
            'error' => $message,
        ];
    }

    /**
     * Transform the Letter model into an array structure that matches
     * what LetterRequest expects (simulating form input).
     */
    protected function prepareLetterData(Letter $letter): array
    {
        // attributesToArray() triggers the 'getCopiesAttribute' accessor automatically
        // giving us the 'copies' array structure needed for validation
        $attributes = $letter->attributesToArray();

        // 1. Handle Booleans
        $boolFields = [
            'date_uncertain', 'date_approximate', 'date_inferred', 'date_is_range',
            'author_uncertain', 'author_inferred',
            'recipient_uncertain', 'recipient_inferred',
            'origin_uncertain', 'origin_inferred',
            'destination_uncertain', 'destination_inferred'
        ];

        foreach ($boolFields as $field) {
            $attributes[$field] = (bool) ($attributes[$field] ?? false);
        }

        // 2. Normalize Date Fields
        $dateFields = ['date_year', 'date_month', 'date_day', 'range_year', 'range_month', 'range_day'];

        foreach ($dateFields as $f) {
            $v = $attributes[$f] ?? null;
            if (is_string($v)) $v = trim($v);
            if ($v === '' || $v === '0' || $v === 0) $v = null;
            $attributes[$f] = $v;
        }

        // 3. Force Range Nullification
        if (empty($attributes['date_is_range'])) {
            $attributes['range_year'] = null;
            $attributes['range_month'] = null;
            $attributes['range_day'] = null;
        }

        // 4. Ensure Arrays
        $attributes['copies'] = $attributes['copies'] ?? [];
        $attributes['related_resources'] = $attributes['related_resources'] ?? [];

        // 5. Map Relationships to Input Format
        $attributes['authors'] = $this->mapIdentitiesToInput($letter, 'author');
        $attributes['recipients'] = $this->mapIdentitiesToInput($letter, 'recipient');
        $attributes['mentioned'] = $this->mapIdentitiesToInput($letter, 'mentioned');
        $attributes['origins'] = $this->mapPlacesToInput($letter, 'origin');
        $attributes['destinations'] = $this->mapPlacesToInput($letter, 'destination');
        $attributes['keywords'] = $this->mapKeywordsToInput($letter);

        return $attributes;
    }

    protected function mapIdentitiesToInput(Letter $letter, string $role): array
    {
        $local = $letter->identities->toBase()->where('pivot.role', $role)->map(fn($i) => ['value' => 'local-' . $i->id]);
        $global = $letter->globalIdentities->toBase()->where('pivot.role', $role)->map(fn($i) => ['value' => 'global-' . $i->id]);

        return $local->merge($global)->values()->toArray();
    }

    protected function mapPlacesToInput(Letter $letter, string $role): array
    {
        $local = $letter->localPlaces->toBase()->where('pivot.role', $role)->map(fn($p) => ['value' => 'local-' . $p->id]);
        $global = $letter->globalPlaces->toBase()->where('pivot.role', $role)->map(fn($p) => ['value' => 'global-' . $p->id]);

        return $local->merge($global)->values()->toArray();
    }

    protected function mapKeywordsToInput(Letter $letter): array
    {
        $local = $letter->localKeywords->toBase()->map(fn($k) => 'local-' . $k->id);
        $global = $letter->globalKeywords->toBase()->map(fn($k) => 'global-' . $k->id);

        return $local->merge($global)->values()->toArray();
    }

    public function render()
    {
        return view('livewire.letters-consistency-check');
    }
}
