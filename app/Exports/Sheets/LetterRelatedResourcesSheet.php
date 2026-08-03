<?php

namespace App\Exports\Sheets;

use Generator;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class LetterRelatedResourcesSheet extends AbstractLetterSheet implements WithColumnWidths
{
    public function title(): string
    {
        return 'Related resources';
    }

    public function headings(): array
    {
        return ['Letter ID', 'Position', 'Title', 'Link', 'Data JSON'];
    }

    public function generator(): Generator
    {
        foreach ($this->letters() as $letter) {
            foreach ($letter->related_resources ?? [] as $index => $resource) {
                $resource = is_array($resource) ? $resource : ['value' => $resource];

                yield [
                    $letter->id,
                    $index + 1,
                    $resource['title'] ?? '',
                    $resource['link'] ?? '',
                    $this->json($resource),
                ];
            }
        }
    }

    public function columnWidths(): array
    {
        return [
            'C' => 40,
            'D' => 60,
            'E' => 70,
        ];
    }
}
