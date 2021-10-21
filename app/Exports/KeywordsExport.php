<?php

namespace App\Exports;

use App\Models\Keyword;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class KeywordsExport implements FromCollection, WithMapping, WithHeadings
{
    public function collection()
    {
        return Keyword::all();
    }

    public function headings(): array
    {
        return [
            'id',
            'cs',
            'en',
            'category',
        ];
    }

    public function map($keyword): array
    {
        $name = $keyword->getTranslations('name');
        $category = $keyword->keyword_category;

        return [
            $keyword->id,
            isset($name['cs']) ? $name['cs'] : '',
            isset($name['en']) ? $name['en'] : '',
            $category ? implode(' | ', array_values($category->getTranslations('name'))) : '',
        ];
    }
}
