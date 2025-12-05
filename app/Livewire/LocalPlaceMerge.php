<?php

namespace App\Livewire;

use App\Services\LocalPlaceMergeService;
use App\Http\Requests\LocalPlaceMergeRequest;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class LocalPlaceMerge extends Component
{
    // Configuration
    public array $criteria;

    // Thresholds
    public int $nameSimilarityThreshold;
    public float $latitudeTolerance;
    public float $longitudeTolerance;
    public int $countryAndNameThreshold;

    public bool $scanComplete = false;

    // Results
    public array $groups = [];

    public function mount()
    {
        // Default configs
        $this->criteria = config('local_place_merge.default_criteria', [
            'geoname_id',
            'alternative_names',
            'country_and_name',
            'name_similarity',
            'coordinates'
        ]);
        $this->nameSimilarityThreshold = config('local_place_merge.name_similarity_threshold', 80);
        $this->countryAndNameThreshold = config('local_place_merge.country_and_name_threshold', 80);
        $this->latitudeTolerance = config('local_place_merge.latitude_tolerance', 0.1);
        $this->longitudeTolerance = config('local_place_merge.longitude_tolerance', 0.1);
    }

    public function scan()
    {
        $service = app(LocalPlaceMergeService::class);

        $options = [
            'name_similarity_threshold' => $this->nameSimilarityThreshold,
            'country_and_name_threshold' => $this->countryAndNameThreshold,
            'latitude_tolerance' => $this->latitudeTolerance,
            'longitude_tolerance' => $this->longitudeTolerance,
        ];

        $results = $service->findCandidates($this->criteria, $options);

        $this->groups = $results->map(function($group, $index) {
            return [
                'id' => $index,
                'reason' => $group['reason'],
                'places' => $group['places']->map(fn($p) => $p->toArray())->toArray()
            ];
        })->toArray();

        $this->scanComplete = true;
    }

    public function resetScan()
    {
        $this->scanComplete = false;
        $this->groups = [];
    }

    public function mergeGroup($groupIndex, $payload)
    {
        $request = new LocalPlaceMergeRequest();
        $validator = Validator::make($payload, $request->rules());

        if ($validator->fails()) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $validator->errors()->first()]);
            return;
        }

        if (count($payload['source_ids']) < 1) {
            $this->dispatch('notify', ['type' => 'error', 'message' => __('hiko.select_at_least_two')]);
            return;
        }

        try {
            app(LocalPlaceMergeService::class)->merge($payload);
            unset($this->groups[$groupIndex]);
            $this->dispatch('notify', ['type' => 'success', 'message' => __('hiko.merge_successful')]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.local-place-merge');
    }
}
