<?php

namespace App\Livewire;

use App\Http\Requests\LocalKeywordMergeRequest;
use App\Models\Keyword;
use App\Services\LocalKeywordMergeService;
use App\Services\PageLockService;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class LocalKeywordMerge extends Component
{
    public bool $scanComplete = false;
    public array $groups = [];
    public array $criteria = [];
    public int $nameSimilarityThreshold;

    public function mount(): void
    {
        $this->criteria = config('local_keyword_merge.default_criteria', ['name_similarity']);
        $this->nameSimilarityThreshold = (int)config('local_keyword_merge.name_similarity_threshold', 80);
    }

    public function scan(): void
    {
        $service = app(LocalKeywordMergeService::class);
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
            'resource_type' => 'keyword_local_merge',
        ], $user);

        if (!$lock['ok']) {
            $this->dispatch('notify', ['type' => 'error', 'message' => __('hiko.page_lock_not_owned')]);
            return;
        }

        $request = new LocalKeywordMergeRequest();
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
            $result = app(LocalKeywordMergeService::class)->merge($payload);

            $sourceIds = $payload['source_ids'];
            $targetId = $payload['target_id'];
            $freshTarget = Keyword::query()->find($targetId);

            $this->groups[$groupIndex]['items'] = collect($this->groups[$groupIndex]['items'])
                ->map(function (array $item) use ($targetId, $freshTarget): array {
                    if ((int)$item['id'] === (int)$targetId && $freshTarget !== null) {
                        return [
                            'id' => $freshTarget->id,
                            'cs' => $freshTarget->getTranslation('name', 'cs'),
                            'en' => $freshTarget->getTranslation('name', 'en'),
                            'keyword_category_id' => $freshTarget->keyword_category_id,
                            'keyword_category_label' => optional($freshTarget->keyword_category)->getTranslation('name', app()->getLocale()) ?? '—',
                            'letters_count' => $freshTarget->letters()->count(),
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
                'url' => route('keywords.edit', $targetId),
                'count' => $result['merged_count'] ?? count($sourceIds),
            ]);

            $this->dispatch('notify', ['type' => 'success', 'html' => $message, 'autoClose' => false]);
        } catch (\Throwable $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.local-keyword-merge');
    }
}
