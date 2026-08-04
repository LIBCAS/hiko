<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MetadataDigestWorkbook implements WithMultipleSheets
{
    public function __construct(private readonly array $section)
    {
    }

    public function sheets(): array
    {
        return array_map(
            fn(array $group) => new MetadataDigestSheet(
                $group['sheet'],
                $this->section['headings'],
                array_column($group['records'], 'export')
            ),
            $this->section['groups']
        );
    }
}
