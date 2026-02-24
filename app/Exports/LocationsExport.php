<?php

namespace App\Exports;

use App\Models\GlobalLocation;
use App\Models\Location;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class LocationsExport implements FromCollection, WithMapping, WithHeadings
{
    public function collection()
    {
        $prefix = tenancy()->initialized ? tenancy()->tenant->table_prefix : null;

        // If not in a tenant context, we can't count usage.
        // We'll return just global locations with 0 count, or handle gracefully.
        if (!$prefix) {
            return GlobalLocation::all()->map(function ($l) {
                $l->source_type = 'Global';
                $l->letters_count = 0;
                return $l;
            });
        }

        $manifestationsTable = "{$prefix}__manifestations";
        $locationsTable = "{$prefix}__locations";

        // Local Locations with Count
        $localSubquery = "(
            SELECT COUNT(DISTINCT letter_id) FROM `{$manifestationsTable}`
            WHERE `{$manifestationsTable}`.repository_id = `{$locationsTable}`.id
               OR `{$manifestationsTable}`.archive_id = `{$locationsTable}`.id
               OR `{$manifestationsTable}`.collection_id = `{$locationsTable}`.id
        )";

        $local = Location::select('*')
            ->selectRaw("({$localSubquery}) as letters_count")
            ->get()
            ->map(function ($l) {
                $l->source_type = 'Local';
                return $l;
            });

        // Global Locations with Count
        $globalSubquery = "(
            SELECT COUNT(DISTINCT letter_id) FROM `{$manifestationsTable}`
            WHERE `{$manifestationsTable}`.global_repository_id = global_locations.id
               OR `{$manifestationsTable}`.global_archive_id = global_locations.id
               OR `{$manifestationsTable}`.global_collection_id = global_locations.id
        )";

        $global = GlobalLocation::select('*')
            ->selectRaw("({$globalSubquery}) as letters_count")
            ->get()
            ->map(function ($l) {
                $l->source_type = 'Global';
                return $l;
            });

        return $local->merge($global);
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'type',
            'source',
            'letters_count',
        ];
    }

    public function map($location): array
    {
        return [
            $location->id,
            $location->name,
            $location->type,
            $location->source_type,
            $location->letters_count,
        ];
    }
}
