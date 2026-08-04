<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MetadataDigestSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        private readonly string $sheetTitle,
        private readonly array $headings,
        private readonly array $rows
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        $title = str_replace(['\\', '/', '?', '*', '[', ']', ':'], '-', $this->sheetTitle);
        $title = $title !== '' ? $title : 'Data';

        return mb_substr($title, 0, 31);
    }
}
