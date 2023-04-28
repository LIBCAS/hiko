<?php

namespace App\Exports;

use App\Models\Profession;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class ProfessionsExport implements FromCollection, WithMapping, WithHeadings
{
    public function collection()
    {
        return Profession::all();
    }

    public function headings(): array
    {
        return [
            'id',
            'cs',
            'en',
        ];
    }

    public function map($profession): array
    {
        $name = $profession->getTranslations('name');

        return [
            $profession->id,
            $name['cs'] ?? '',
            $name['en'] ?? '',
        ];
    }
}
