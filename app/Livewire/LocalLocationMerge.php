<?php

namespace App\Livewire;

use App\Services\LocalLocationMergeService;
use App\Http\Requests\LocalLocationMergeRequest;
use App\Services\PageLockService;
use App\Models\Location;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class LocalLocationMerge extends Component
{
    public bool $scanComplete = false;
    public array $groups = [];
    public array $criteria;
    public int $nameSimilarityThreshold;
    public array $filters = [
        'name' => '',
        'type' => '',
    ];

    public function mount()
    {
        $this->criteria = config('local_location_merge.default_criteria', ['name_similarity', 'type']);
        $this->nameSimilarityThreshold = config('local_location_merge.name_similarity_threshold', 0);
    }

    public function scan()
    {
        $service = app(LocalLocationMergeService::class);

        $options = [
            'name_similarity_threshold' => $this->nameSimilarityThreshold,
        ];

        $results = $service->findCandidates($this->criteria, $options);

        $this->groups = is_array($results) ? $results : $results->toArray();

        $this->scanComplete = true;
    }

    public function resetScan()
    {
        $this->scanComplete = false;
        $this->groups = [];
    }

    public function mergeGroup($groupIndex, $payload)
    {
        $user = auth()->user();
        if (!$user) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Unauthenticated.']);
            return;
        }

        $lock = app(PageLockService::class)->assertOwned([
            'scope' => 'tenant',
            'resource_type' => 'location_local_merge',
        ], $user);

        if (!$lock['ok']) {
            $this->dispatch('notify', ['type' => 'error', 'message' => __('hiko.page_lock_not_owned')]);
            return;
        }

        $request = new LocalLocationMergeRequest();
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
            app(LocalLocationMergeService::class)->merge($payload);

            $sourceIds = $payload['source_ids'];
            $targetId = $payload['target_id'];

            // Fetch fresh target data
            $freshTarget = Location::find($targetId);

            // Update the group list in UI
            $this->groups[$groupIndex]['items'] = collect($this->groups[$groupIndex]['items'])
                ->map(function ($item) use ($targetId, $freshTarget) {
                    if ($item['id'] == $targetId && $freshTarget) {
                        return $freshTarget->toArray();
                    }
                    return $item;
                })
                ->reject(function ($item) use ($sourceIds) {
                    return in_array($item['id'], $sourceIds);
                })
                ->values()
                ->toArray();

            // Remove group if fewer than 2 items left
            if (count($this->groups[$groupIndex]['items']) < 2) {
                unset($this->groups[$groupIndex]);
            }

            $message = __('hiko.successfully_merged_into_id', [
                'id'  => $targetId,
                'url' => route('locations.edit', $targetId),
                'count' => count($sourceIds)
            ]);

            $this->dispatch('notify', ['type' => 'success', 'html' => $message, 'autoClose' => false]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        // Filter groups for display
        $filteredGroups = collect($this->groups)->filter(function ($group) {
            if (!empty($this->filters['name'])) {
                $search = mb_strtolower($this->filters['name']);
                // Check if any item in group matches search
                $found = collect($group['items'])->contains(function($item) use ($search) {
                    return str_contains(mb_strtolower($item['name']), $search);
                });
                if (!$found) return false;
            }

            if (!empty($this->filters['type'])) {
                $type = $this->filters['type'];
                $firstItem = $group['items'][0] ?? null;
                if ($firstItem && $firstItem['type'] !== $type) {
                    return false;
                }
            }
            return true;
        });

        return view('livewire.local-location-merge', [
            'filteredGroups' => $filteredGroups
        ]);
    }
}
