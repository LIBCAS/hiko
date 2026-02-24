<?php

namespace App\Services;

use App\Models\GlobalProfession;
use App\Models\GlobalProfessionCategory;
use App\Models\MergeAuditLog;
use App\Models\Profession;
use App\Models\ProfessionCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GlobalProfessionMergeService
{
    public function previewMerges(array $criteria, array $options = [], array $filters = []): Collection
    {
        if (!tenancy()->initialized) {
            return collect();
        }

        $localProfessions = Profession::query()->with('profession_category');
        $locale = app()->getLocale() === 'en' ? 'en' : 'cs';

        if (!empty($filters['name'])) {
            $term = mb_strtolower(trim((string)$filters['name']));
            $localProfessions->whereRaw(
                "LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$locale}\"'))) LIKE ?",
                ["%{$term}%"]
            );
        }

        $preview = collect();

        foreach ($localProfessions->get() as $local) {
            $strategy = $this->determineMergeStrategy($local, $criteria, $options);
            $isMoveWithoutCategory = $strategy['type'] === 'move' && empty($local->profession_category_id);
            $selectable = !$isMoveWithoutCategory;
            $reason = $isMoveWithoutCategory
                ? __('hiko.profession_move_requires_category')
                : $strategy['reason'];

            if (!empty($filters['strategy']) && $filters['strategy'] !== 'all' && $filters['strategy'] !== $strategy['type']) {
                continue;
            }

            $preview->push([
                'local' => $local,
                'strategy' => $strategy['type'],
                'global' => $strategy['global_profession'],
                'reason' => $reason,
                'selectable' => $selectable,
            ]);
        }

        return $preview;
    }

    public function determineMergeStrategy(Profession $local, array $criteria, array $options = []): array
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'cs';
        $threshold = (int)($options['name_similarity_threshold'] ?? 80);

        $localName = $this->normalizeString($local->getTranslation('name', $locale));
        $bestMatch = null;
        $bestSimilarity = 0.0;

        foreach (GlobalProfession::query()->get() as $global) {
            $globalName = $this->normalizeString($global->getTranslation('name', $locale));
            if ($localName === '' || $globalName === '') {
                continue;
            }

            $pct = 0.0;
            similar_text($localName, $globalName, $pct);

            if ($pct >= $threshold && $pct > $bestSimilarity) {
                $bestSimilarity = $pct;
                $bestMatch = $global;
            }
        }

        if ($bestMatch !== null) {
            return [
                'type' => 'merge',
                'global_profession' => $bestMatch,
                'reason' => __('hiko.similarity_percentage', ['percent' => round($bestSimilarity, 2)]),
            ];
        }

        return [
            'type' => 'move',
            'global_profession' => null,
            'reason' => __('hiko.no_match'),
        ];
    }

    public function executeMerge(array $selectedIds, array $criteria, array $options = []): array
    {
        $summary = [
            'success' => true,
            'merged' => 0,
            'created' => 0,
            'skipped' => 0,
            'errors' => [],
            'items' => [],
        ];

        $payload = [
            'selected_ids' => $selectedIds,
            'criteria' => $criteria,
            'options' => $options,
        ];

        DB::beginTransaction();
        try {
            $tenantPrefix = tenancy()->tenant->table_prefix;
            $pivotTable = "{$tenantPrefix}__identity_profession";

            $locals = Profession::query()->with('profession_category')->whereIn('id', $selectedIds)->get();

            foreach ($locals as $local) {
                try {
                    $strategy = $this->determineMergeStrategy($local, $criteria, $options);

                    if ($strategy['type'] === 'merge') {
                        $global = $strategy['global_profession'];
                        $summary['merged']++;
                        $action = 'merge';
                    } else {
                        if (empty($local->profession_category_id)) {
                            throw new \RuntimeException(__('hiko.profession_move_requires_category'));
                        }

                        $globalCategoryId = $this->findOrCreateGlobalCategoryId($local->profession_category_id);

                        $global = GlobalProfession::query()->create([
                            'name' => [
                                'cs' => trim((string)$local->getTranslation('name', 'cs')),
                                'en' => trim((string)$local->getTranslation('name', 'en')),
                            ],
                            'profession_category_id' => $globalCategoryId,
                        ]);

                        $summary['created']++;
                        $action = 'move';
                    }

                    $this->relinkIdentities($pivotTable, (int)$local->id, (int)$global->id);
                    $local->delete();

                    $summary['items'][] = [
                        'local_id' => $local->id,
                        'global_id' => $global->id,
                        'strategy' => $action,
                    ];
                } catch (\Throwable $itemError) {
                    $summary['skipped']++;
                    $summary['errors'][] = "Profession {$local->id}: {$itemError->getMessage()}";
                }
            }

            DB::commit();

            $this->logAudit('success', $payload, $summary);
            Log::info('[GlobalProfessionMerge] success', $summary);

            return $summary;
        } catch (\Throwable $e) {
            DB::rollBack();

            $summary['success'] = false;
            $summary['error'] = $e->getMessage();

            $this->logAudit('error', $payload, $summary, $e->getMessage());
            Log::error('[GlobalProfessionMerge] error: ' . $e->getMessage(), ['payload' => $payload]);

            return $summary;
        }
    }

    private function relinkIdentities(string $pivotTable, int $localProfessionId, int $globalProfessionId): void
    {
        $links = DB::table($pivotTable)->where('profession_id', $localProfessionId)->get();

        foreach ($links as $link) {
            $alreadyLinked = DB::table($pivotTable)
                ->where('identity_id', $link->identity_id)
                ->where('global_profession_id', $globalProfessionId)
                ->exists();

            if ($alreadyLinked) {
                DB::table($pivotTable)
                    ->where('identity_id', $link->identity_id)
                    ->where('profession_id', $localProfessionId)
                    ->delete();

                continue;
            }

            DB::table($pivotTable)
                ->where('identity_id', $link->identity_id)
                ->where('profession_id', $localProfessionId)
                ->update([
                    'profession_id' => null,
                    'global_profession_id' => $globalProfessionId,
                ]);
        }
    }

    private function findOrCreateGlobalCategoryId(?int $localCategoryId): ?int
    {
        if ($localCategoryId === null) {
            return null;
        }

        $localCategory = ProfessionCategory::query()->find($localCategoryId);
        if ($localCategory === null) {
            return null;
        }

        $localCs = $this->normalizeString($localCategory->getTranslation('name', 'cs'));
        $localEn = $this->normalizeString($localCategory->getTranslation('name', 'en'));

        foreach (GlobalProfessionCategory::query()->get() as $globalCategory) {
            $globalCs = $this->normalizeString($globalCategory->getTranslation('name', 'cs'));
            $globalEn = $this->normalizeString($globalCategory->getTranslation('name', 'en'));

            if ($localCs === $globalCs && $localEn === $globalEn) {
                return (int)$globalCategory->id;
            }
        }

        $created = GlobalProfessionCategory::query()->create([
            'name' => [
                'cs' => trim((string)$localCategory->getTranslation('name', 'cs')),
                'en' => trim((string)$localCategory->getTranslation('name', 'en')),
            ],
        ]);

        return (int)$created->id;
    }

    private function normalizeString(?string $value): string
    {
        return trim(mb_strtolower((string)$value));
    }

    private function logAudit(string $status, array $payload, array $result = [], ?string $errorMessage = null): void
    {
        try {
            $user = auth()->user();

            MergeAuditLog::query()->create([
                'tenant_id' => tenancy()->tenant?->id,
                'tenant_prefix' => tenancy()->tenant?->table_prefix,
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'entity' => 'profession',
                'operation' => 'global_merge',
                'status' => $status,
                'payload' => $payload,
                'result' => $result,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            Log::error('[GlobalProfessionMerge] failed to persist audit log: ' . $e->getMessage());
        }
    }
}
