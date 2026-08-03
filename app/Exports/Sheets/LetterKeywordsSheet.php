<?php

namespace App\Exports\Sheets;

use Generator;

class LetterKeywordsSheet extends AbstractLetterSheet
{
    public function title(): string
    {
        return 'Keywords';
    }

    public function headings(): array
    {
        return [
            'Letter ID', 'Scope', 'Keyword ID', 'Keyword Reference', 'Name CS', 'Name EN',
            'Category ID', 'Category Reference', 'Category Name CS', 'Category Name EN',
        ];
    }

    public function generator(): Generator
    {
        foreach ($this->letters(['localKeywords.keyword_category', 'globalKeywords.keyword_category']) as $letter) {
            $rows = $letter->localKeywords
                ->map(fn ($keyword) => ['scope' => 'local', 'keyword' => $keyword])
                ->concat($letter->globalKeywords->map(
                    fn ($keyword) => ['scope' => 'global', 'keyword' => $keyword]
                ))
                ->sortBy(fn ($row) => sprintf(
                    '%s-%020d',
                    $row['scope'],
                    $row['keyword']->id
                ));

            foreach ($rows as $row) {
                $keyword = $row['keyword'];
                $scope = $row['scope'];
                $category = $keyword->keyword_category;

                yield [
                    $letter->id,
                    $scope,
                    $keyword->id,
                    $this->reference($scope, $keyword->id),
                    $this->translation($keyword, 'name', 'cs'),
                    $this->translation($keyword, 'name', 'en'),
                    $category?->id,
                    $category ? $this->reference($scope, $category->id) : '',
                    $category ? $this->translation($category, 'name', 'cs') : '',
                    $category ? $this->translation($category, 'name', 'en') : '',
                ];
            }
        }
    }
}
