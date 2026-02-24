<?php

namespace App\Exports;

use App\Models\Place;
use App\Models\GlobalPlace;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

class PlacesExport extends \PhpOffice\PhpSpreadsheet\Cell\StringValueBinder implements WithCustomValueBinder, FromCollection, WithMapping, WithHeadings
{
    public function collection()
    {
        $localPlaces = Place::all()->map(function ($place) {
            $place->setAttribute('source_type', __('hiko.local'));
            return $place;
        });

        $globalPlaces = GlobalPlace::all()->map(function ($place) {
            $place->setAttribute('source_type', __('hiko.global'));
            return $place;
        });

        return $localPlaces->concat($globalPlaces);
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'division',
            'country',
            'note',
            'latitude',
            'longitude',
            'geoname_id',
            'source',
        ];
    }

    public function map($place): array
    {
        return [
            $place->id,
            $place->name,
            $place->division,
            $place->country,
            $place->note,
            $place->latitude,
            $place->longitude,
            $place->geoname_id,
            $place->source_type,
        ];
    }
}
