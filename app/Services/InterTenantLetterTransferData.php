<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InterTenantLetterTransferData
{
    public function load(Tenant $tenant, array $letterIds): array
    {
        $ids = collect($letterIds)->map(fn ($id) => (int) $id)->unique()->values();
        $prefix = $tenant->table_prefix . '__';
        $letters = DB::connection('tenant')->table("{$prefix}letters")
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        if ($letters->count() !== $ids->count()) {
            $missing = $ids->diff($letters->pluck('id')->map(fn ($id) => (int) $id))->values()->all();
            throw new RuntimeException(__('hiko.transfer_source_records_missing', ['ids' => implode(', ', $missing)]));
        }

        $identityRows = DB::connection('tenant')->table("{$prefix}identity_letter")
            ->whereIn('letter_id', $ids)
            ->get();
        $placeRows = DB::connection('tenant')->table("{$prefix}letter_place")
            ->whereIn('letter_id', $ids)
            ->get();
        $keywordRows = DB::connection('tenant')->table("{$prefix}keyword_letter")
            ->whereIn('letter_id', $ids)
            ->get();
        $manifestations = DB::connection('tenant')->table("{$prefix}manifestations")
            ->whereIn('letter_id', $ids)
            ->get();
        $media = DB::connection('tenant')->table("{$prefix}media")
            ->where('model_type', \App\Models\Letter::class)
            ->whereIn('model_id', $ids)
            ->orderBy('order_column')
            ->get();

        return [
            'letters' => $letters,
            'identity_rows' => $identityRows,
            'place_rows' => $placeRows,
            'keyword_rows' => $keywordRows,
            'manifestations' => $manifestations,
            'media' => $media,
            'dependencies' => [
                'identities' => $this->entities("{$prefix}identities", $identityRows->pluck('identity_id')),
                'places' => $this->entities("{$prefix}places", $placeRows->pluck('place_id')),
                'keywords' => $this->entities("{$prefix}keywords", $keywordRows->pluck('keyword_id')),
                'locations' => $this->locations("{$prefix}locations", $manifestations),
            ],
            'global_dependencies' => [
                'identities' => $this->entities('global_identities', $identityRows->pluck('global_identity_id')),
                'places' => $this->entities('global_places', $placeRows->pluck('global_place_id')),
                'keywords' => $this->entities('global_keywords', $keywordRows->pluck('global_keyword_id')),
                'locations' => $this->globalLocations($manifestations),
            ],
        ];
    }

    private function entities(string $table, Collection $ids): Collection
    {
        $ids = $ids->filter()->map(fn ($id) => (int) $id)->unique()->values();

        return $ids->isEmpty()
            ? collect()
            : DB::connection('tenant')->table($table)->whereIn('id', $ids)->orderBy('id')->get();
    }

    private function locations(string $table, Collection $manifestations): Collection
    {
        $ids = $manifestations
            ->flatMap(fn ($row) => [$row->repository_id, $row->archive_id, $row->collection_id])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return $ids->isEmpty()
            ? collect()
            : DB::connection('tenant')->table($table)->whereIn('id', $ids)->orderBy('type')->orderBy('name')->get();
    }

    private function globalLocations(Collection $manifestations): Collection
    {
        $ids = $manifestations
            ->flatMap(fn ($row) => [
                $row->global_repository_id,
                $row->global_archive_id,
                $row->global_collection_id,
            ])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return $ids->isEmpty()
            ? collect()
            : DB::connection('tenant')->table('global_locations')->whereIn('id', $ids)->orderBy('type')->orderBy('name')->get();
    }
}
