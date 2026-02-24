<?php

namespace App\Livewire;

use App\Http\Requests\LocalProfessionMergeRequest;
use App\Models\Profession;
use App\Services\LocalProfessionMergeService;
use App\Services\PageLockService;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class LocalProfessionMerge extends Component
{
    public bool $scanComplete = false;
    public array $groups = [];
    public array $criteria = [];
    public int $nameSimilarityThreshold;

    public function mount(): void
    {
        $this->criteria = config('local_profession_merge.default_criteria', ['name_similarity']);
        $this->nameSimilarityThreshold = (int)config('local_profession_merge.name_similarity_threshold', 80);
    }

    public function scan(): void
    {
        $service = app(LocalProfessionMergeService::class);
        $this->groups = $service->findCandidates($this->criteria, [
            'name_similarity_threshold' => $this->nameSimilarityThreshold,
        ])->toArray();

        $this->scanComplete = true;
    }

    public function resetScan(): void
    {
        $this->scanComplete = false;
        $this->groups = [];
    }

    public function mergeGroup(int $groupIndex, array $payload): void
    {
        $user = auth()->user();
        if (!$user) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Unauthenticated.']);
            return;
        }

        $lock = app(PageLockService::class)->assertOwned([
            'scope' => 'tenant',
            'resource_type' => 'profession_local_merge',
        ], $user);

        if (!$lock['ok']) {
            $this->dispatch('notify', ['type' => 'error', 'message' => __('hiko.page_lock_not_owned')]);
            return;
        }

        $request = new LocalProfessionMergeRequest();
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
            $result = app(LocalProfessionMergeService::class)->merge($payload);

            $sourceIds = $payload['source_ids'];
            $targetId = $payload['target_id'];
            $freshTarget = Profession::query()->find($targetId);

            $this->groups[$groupIndex]['items'] = collect($this->groups[$groupIndex]['items'])
                ->map(function (array $item) use ($targetId, $freshTarget): array {
                    if ((int)$item['id'] === (int)$targetId && $freshTarget !== null) {
                        return [
                            'id' => $freshTarget->id,
                            'cs' => $freshTarget->getTranslation('name', 'cs'),
                            'en' => $freshTarget->getTranslation('name', 'en'),
                            'profession_category_id' => $freshTarget->profession_category_id,
                            'profession_category_label' => optional($freshTarget->profession_category)->getTranslation('name', app()->getLocale()) ?? '—',
                            'identities_count' => $freshTarget->identities()->count(),
                            'created_at' => $freshTarget->created_at,
                        ];
                    }

                    return $item;
                })
                ->reject(fn (array $item): bool => in_array($item['id'], $sourceIds))
                ->values()
                ->toArray();

            if (count($this->groups[$groupIndex]['items']) < 2) {
                unset($this->groups[$groupIndex]);
            }

            $message = __('hiko.successfully_merged_into_id', [
                'id' => $targetId,
                'url' => route('professions.edit', $targetId),
                'count' => $result['merged_count'] ?? count($sourceIds),
            ]);

            $this->dispatch('notify', ['type' => 'success', 'html' => $message, 'autoClose' => false]);
        } catch (\Throwable $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.local-profession-merge');
    }
}
