<?php

namespace App\Exports;

use App\Models\KeywordCategory;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class KeywordCategoriesExport implements FromCollection, WithMapping, WithHeadings
{
    public function collection()
    {
        return KeywordCategory::all();
    }

    public function headings(): array
    {
        return [
            'id',
            'cs',
            'en',
        ];
    }

    public function map($keyword): array
    {
        $name = $keyword->getTranslations('name');

        return [
            $keyword->id,
            $name['cs'] ?? '',
            $name['en'] ?? '',
        ];
    }
}
