<?php

namespace App\Exports;

use App\Exports\Sheets\LetterIdentitiesSheet;
use App\Exports\Sheets\LetterKeywordsSheet;
use App\Exports\Sheets\LetterManifestationsSheet;
use App\Exports\Sheets\LetterPlacesSheet;
use App\Exports\Sheets\LetterRelatedResourcesSheet;
use App\Exports\Sheets\LettersSheet;
use App\Services\LetterFilterService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LettersExport implements WithMultipleSheets
{
    private array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = app(LetterFilterService::class)->normalize($filters);
    }

    public function sheets(): array
    {
        return [
            new LettersSheet($this->filters),
            new LetterIdentitiesSheet($this->filters),
            new LetterPlacesSheet($this->filters),
            new LetterKeywordsSheet($this->filters),
            new LetterManifestationsSheet($this->filters),
            new LetterRelatedResourcesSheet($this->filters),
        ];
    }
}
