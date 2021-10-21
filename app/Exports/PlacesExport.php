<?php

namespace App\Exports;

use App\Models\Place;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

class PlacesExport extends \PhpOffice\PhpSpreadsheet\Cell\StringValueBinder implements WithCustomValueBinder, FromCollection, WithMapping, WithHeadings
{
    public function collection()
    {
        return Place::all();
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'country',
            'note',
            'latitude',
            'longitude',
            'geoname_id',
        ];
    }

    public function map($place): array
    {
        return [
            $place->id,
            $place->name,
            $place->country,
            $place->note,
            $place->latitude,
            $place->longitude,
            $place->geoname_id,
        ];
    }
}
