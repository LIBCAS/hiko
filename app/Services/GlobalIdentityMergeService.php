<?php

namespace App\Services;

use App\Models\GlobalIdentity;
use App\Models\Identity;
use App\Models\MergeAuditLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GlobalIdentityMergeService
{
    public function previewLinks(array $criteria, array $options = [], array $filters = []): Collection
    {
        if (!tenancy()->initialized) {
            return collect();
        }

        $localIdentities = Identity::query()->whereNull('global_identity_id');

        if (!empty($filters['name'])) {
            $localIdentities->where('name', 'like', '%' . trim((string)$filters['name']) . '%');
        }

        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            $localIdentities->where('type', $filters['type']);
        }

        return $localIdentities
            ->orderBy('name')
            ->get()
            ->map(function (Identity $local) use ($criteria, $options) {
                $suggested = $this->findBestGlobalMatch($local, $criteria, $options);

                return [
                    'local' => $local,
                    'strategy' => 'link',
                    'global' => $suggested['global'],
                    'reason' => $suggested['reason'],
                ];
            })
            ->values();
    }

    public function executeLinks(array $selectedIds, array $selectedGlobalIds, array $criteria, array $options = []): array
    {
        $selectedIds = array_map('intval', $selectedIds);
        $selectedGlobalIds = collect($selectedGlobalIds)
            ->only($selectedIds)
            ->mapWithKeys(fn($value, $key) => [(int)$key => (int)$value])
            ->toArray();

        $result = [
            'success' => true,
            'linked' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $payload = [
            'selected_ids' => $selectedIds,
            'selected_global_ids' => $selectedGlobalIds,
            'criteria' => $criteria,
            'options' => $options,
        ];

        try {
            $locals = Identity::query()->whereIn('id', $selectedIds)->get()->keyBy('id');

            foreach ($selectedIds as $localId) {
                $local = $locals->get((int)$localId);
                $selectedGlobalId = isset($selectedGlobalIds[$localId]) ? (int)$selectedGlobalIds[$localId] : null;

                if (!$local) {
                    $result['skipped']++;
                    $result['errors'][] = "Local identity {$localId}: not found.";
                    continue;
                }

                if (!empty($local->global_identity_id)) {
                    $result['skipped']++;
                    $result['errors'][] = "Local identity {$local->id}: already linked.";
                    continue;
                }

                if (!$selectedGlobalId) {
                    $result['skipped']++;
                    $result['errors'][] = "Local identity {$local->id}: no global identity selected.";
                    continue;
                }

                $global = GlobalIdentity::query()->find($selectedGlobalId);
                if (!$global) {
                    $result['skipped']++;
                    $result['errors'][] = "Local identity {$local->id}: selected global identity not found.";
                    continue;
                }

                if ($local->type !== $global->type) {
                    $result['skipped']++;
                    $result['errors'][] = "Local identity {$local->id}: type mismatch.";
                    continue;
                }

                $local->update(['global_identity_id' => $global->id]);
                $result['linked']++;
            }

            $this->logAudit('success', $payload, $result);
            Log::info('[GlobalIdentityMerge] success', $result);

            return $result;
        } catch (\Throwable $e) {
            $result['success'] = false;
            $result['error'] = $e->getMessage();

            $this->logAudit('error', $payload, $result, $e->getMessage());
            Log::error('[GlobalIdentityMerge] error: ' . $e->getMessage(), [
                'payload' => $payload,
                'trace' => $e->getTraceAsString(),
            ]);

            return $result;
        }
    }

    private function findBestGlobalMatch(Identity $local, array $criteria, array $options = []): array
    {
        $threshold = (int)($options['name_similarity_threshold'] ?? 80);
        $useSimilarity = in_array('name_similarity', $criteria, true);

        $globals = GlobalIdentity::query()
            ->where('type', $local->type)
            ->get();

        $localName = $this->normalizeString($local->name);
        $localSurname = $this->normalizeString($local->surname);
        $localForename = $this->normalizeString($local->forename);
        $localFull = trim($localSurname . ' ' . $localForename);
        $best = null;
        $bestPct = 0.0;

        foreach ($globals as $global) {
            $globalName = $this->normalizeString($global->name);
            $globalSurname = $this->normalizeString($global->surname);
            $globalForename = $this->normalizeString($global->forename);
            $globalFull = trim($globalSurname . ' ' . $globalForename);
            if ($localName === '' || $globalName === '') {
                continue;
            }

            if ($useSimilarity) {
                $pctName = 0.0;
                similar_text($localName, $globalName, $pctName);

                $pctFull = 0.0;
                if ($localFull !== '' && $globalFull !== '') {
                    similar_text($localFull, $globalFull, $pctFull);
                }

                $pctSurname = 0.0;
                if ($localSurname !== '' && $globalSurname !== '') {
                    similar_text($localSurname, $globalSurname, $pctSurname);
                }

                $pct = max($pctName, $pctFull, $pctSurname);
                if ($pct >= $threshold && $pct > $bestPct) {
                    $bestPct = $pct;
                    $best = $global;
                }
            } elseif ($localName === $globalName) {
                $best = $global;
                $bestPct = 100.0;
                break;
            }
        }

        if ($best) {
            return [
                'global' => $best,
                'reason' => __('hiko.similarity_percentage', ['percent' => round($bestPct, 2)]),
            ];
        }

        return [
            'global' => null,
            'reason' => __('hiko.no_match'),
        ];
    }

    private function normalizeString(?string $value): string
    {
        $text = trim((string)$value);
        $text = mb_strtolower($text);

        if (function_exists('removeAccents')) {
            $text = removeAccents($text);
        }

        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', (string)$text);

        return trim((string)$text);
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
                'entity' => 'identity',
                'operation' => 'global_merge',
                'status' => $status,
                'payload' => $payload,
                'result' => $result,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            Log::error('[GlobalIdentityMerge] failed to persist audit log: ' . $e->getMessage());
        }
    }
}
