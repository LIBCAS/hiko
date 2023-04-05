<?php

namespace App\Exports;

use App\Models\Location;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class LocationsExport implements FromCollection, WithMapping, WithHeadings
{
    public function collection()
    {
        return Location::all();
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'type',
        ];
    }

    public function map($location): array
    {
        return [
            $location->id,
            $location->name,
            $location->type,
        ];
    }
}
