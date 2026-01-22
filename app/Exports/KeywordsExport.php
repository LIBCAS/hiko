<?php

namespace App\Exports;

use App\Models\GlobalKeyword;
use App\Models\Keyword;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class KeywordsExport implements FromCollection, WithMapping, WithHeadings
{
    public function collection()
    {
        $local = Keyword::all()->map(function ($item) {
            $item->setAttribute('source_type', __('hiko.local'));
            return $item;
        });

        $global = GlobalKeyword::all()->map(function ($item) {
            $item->setAttribute('source_type', __('hiko.global'));
            return $item;
        });

        return $local->concat($global);
    }

    public function headings(): array
    {
        return [
            'id',
            'cs',
            'en',
            'category',
            'source',
        ];
    }

    public function map($keyword): array
    {
        $name = $keyword->getTranslations('name');
        $category = $keyword->keyword_category;

        return [
            $keyword->id,
            $name['cs'] ?? '',
            $name['en'] ?? '',
            $category ? implode(' | ', array_values($category->getTranslations('name'))) : '',
            $keyword->source_type,
        ];
    }
}
