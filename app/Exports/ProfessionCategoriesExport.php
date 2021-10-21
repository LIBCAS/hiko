<?php

namespace App\Exports;

use App\Models\ProfessionCategory;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class ProfessionCategoriesExport implements FromCollection, WithMapping, WithHeadings
{
    public function collection()
    {
        return ProfessionCategory::all();
    }

    public function headings(): array
    {
        return [
            'id',
            'cs',
            'en',
        ];
    }

    public function map($professionCategory): array
    {
        $name = $professionCategory->getTranslations('name');

        return [
            $professionCategory->id,
            isset($name['cs']) ? $name['cs'] : '',
            isset($name['en']) ? $name['en'] : '',
        ];
    }
}
