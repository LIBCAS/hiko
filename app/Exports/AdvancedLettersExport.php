<?php

namespace App\Exports;

use App\Exports\Sheets\LettersSummarySheet;
use App\Services\LetterFilterService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AdvancedLettersExport implements WithMultipleSheets
{
    private array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = app(LetterFilterService::class)->normalize($filters);
    }

    public function sheets(): array
    {
        return [new LettersSummarySheet($this->filters)];
    }
}
