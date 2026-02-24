<?php

namespace App\Livewire;

use App\Services\LocalIdentityMergeService;
use App\Services\PageLockService;
use App\Http\Requests\LocalIdentityMergeRequest;
use App\Models\Identity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class LocalIdentityMerge extends Component
{
    // Configuration
    public array $criteria;
    public int $nameSimilarityThreshold;
    public bool $scanComplete = false;
    public array $groups = [];

    // Filters
    public array $filters = [
        'type' => '',
        'name' => '',
    ];

    public function mount()
    {
        $this->criteria = config('local_identity_merge.default_criteria', ['viaf_id', 'name_similarity']);
        $this->nameSimilarityThreshold = config('local_identity_merge.name_similarity_threshold', 80);
    }

    public function scan()
    {
        $service = app(LocalIdentityMergeService::class);
        $options = ['name_similarity_threshold' => $this->nameSimilarityThreshold];

        $results = $service->findCandidates($this->criteria, $options);

        $this->groups = $results->map(function($group, $index) {
            return [
                'id' => $index,
                'reason' => $group['reason'],
                'items' => $group['items']->map(fn($i) => $i->toArray())->toArray()
            ];
        })->toArray();

        $this->scanComplete = true;
    }

    public function resetScan()
    {
        $this->scanComplete = false;
        $this->groups = [];
        $this->reset('filters');
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
            'resource_type' => 'identity_local_merge',
        ], $user);

        if (!$lock['ok']) {
            $this->dispatch('notify', ['type' => 'error', 'message' => __('hiko.page_lock_not_owned')]);
            return;
        }

        $request = new LocalIdentityMergeRequest();

        $request->merge($payload);

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
            app(LocalIdentityMergeService::class)->merge($payload);

            $sourceIds = $payload['source_ids'];
            $targetId = $payload['target_id'];

            // Fetch fresh identity with relations
            $freshTarget = Identity::with(['professions', 'globalProfessions', 'religions'])->find($targetId);

            // Prepare formatted lists for UI (replicating Service logic)
            if ($freshTarget) {
                $locale = app()->getLocale();

                // Format Religions
                $religionIds = $freshTarget->religions->pluck('id')->toArray();
                $religionMap = DB::table('religion_translations')
                    ->whereIn('religion_id', $religionIds)
                    ->where('locale', $locale)
                    ->pluck('path_text', 'religion_id');

                $freshTarget->religions_list = $freshTarget->religions->map(function ($r) use ($religionMap) {
                    return $religionMap[$r->id] ?? $r->name;
                })->toArray();

                // Format Professions
                $local = $freshTarget->professions->toBase()->map(function ($p) use ($locale) {
                    $name = json_decode($p->name, true)[$locale] ?? $p->name;
                    return $name . ' (L)';
                });
                $global = $freshTarget->globalProfessions->toBase()->map(function ($p) use ($locale) {
                    $name = $p->getTranslation('name', $locale);
                    return $name . ' (G)';
                });
                $freshTarget->professions_list = $local->merge($global)->toArray();
            }

            // Update the group list
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

            // Remove the group if it now has fewer than 2 items
            if (count($this->groups[$groupIndex]['items']) < 2) {
                unset($this->groups[$groupIndex]);
            }

            $message = __('hiko.successfully_merged_into_id', [
                'id'  => $targetId,
                'url' => route('identities.edit', $targetId),
                'count' => count($sourceIds)
            ]);

            $this->dispatch('notify', ['type' => 'success', 'html' => $message, 'autoClose' => false]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function render()
    {
        // Filter groups
        $filteredGroups = collect($this->groups)->filter(function ($group) {
            // Filter by Type
            if (!empty($this->filters['type'])) {
                $firstItemType = $group['items'][0]['type'] ?? '';
                if ($firstItemType !== $this->filters['type']) {
                    return false;
                }
            }

            // Filter by Name (searches inside items)
            if (!empty($this->filters['name'])) {
                $search = mb_strtolower($this->filters['name']);
                $found = false;
                foreach ($group['items'] as $item) {
                    if (str_contains(mb_strtolower($item['name']), $search)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) return false;
            }

            return true;
        });

        return view('livewire.local-identity-merge', [
            'filteredGroups' => $filteredGroups
        ]);
    }
}
