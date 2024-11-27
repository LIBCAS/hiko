<?php

namespace App\Exports;

use App\Models\Letter;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PalladioCharacterExport implements FromQuery, WithMapping, WithHeadings, WithStyles, WithChunkReading, ShouldAutoSize
{
    /**
     * Query for fetching letters with necessary relations.
     */
    public function query()
    {
        return Letter::query()->with([
            'authors',
            'recipients',
            'origins',
            'destinations',
            'keywords'
        ]);
    }

    /**
     * Define the headings for the export.
     */
    public function headings(): array
    {
        return [
            [
                'General Information', '', '', '',
                'Date Details', '', '', '', '', '', '', '',
                'Authors', '', '',
                'Recipients', '', '',
                'Origins', '', '',
                'Destinations', '', '',
                'Keywords', '',
                'Related Resources', '',
                'Copies Metadata', '', '', '', '', '', '', '', '', '',
                'Content Summary', '', '', '', '', '', '', '', '', ''
            ],
            [
                'ID', 'UUID', 'Created At', 'Updated At',
                'Year', 'Month', 'Day', 'Marked Date',
                'Uncertain Date', 'Approximate Date', 'Inferred Date',
                'Date Range', 'Date Notes',
                'Author Names', 'Author Notes', 'Author Salutations',
                'Recipient Names', 'Recipient Notes', 'Recipient Salutations',
                'Origin Places', 'Origin Notes',
                'Destination Places', 'Destination Notes',
                'Keywords List',
                'Resource Title', 'Resource Link',
                'Manuscript Manifestation', 'Document Type', 'Preservation State', 'Copy Type',
                'Manifestation Notes', 'Letter Number', 'Repository', 'Archive',
                'Collection', 'Shelfmark', 'Location Notes',
                'Explicit Text', 'Incipit Text', 'Content Preview', 'Abstract (CS)', 'Abstract (EN)',
                'Languages Used', 'Private Notes', 'Public Notes', 'Letter Status', 'History/Changes'
            ]
        ];
    }

    /**
     * Map letter data to rows in the export.
     */
    public function map($letter): array
    {
        return array_merge(
            $this->mapGeneralInfo($letter),
            $this->mapDateInfo($letter),
            $this->mapIdentities($letter->authors),
            $this->mapIdentities($letter->recipients),
            $this->mapPlaces($letter->origins),
            $this->mapPlaces($letter->destinations),
            [$this->processKeywords($letter->keywords)],
            $this->processRelatedResources($letter->related_resources),
            $this->processCopies($letter->copies),
            $this->mapContentSummary($letter)
        );
    }

    protected function mapGeneralInfo($letter): array
    {
        return [
            $letter->id,
            $letter->uuid,
            $letter->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
            $letter->updated_at?->format('Y-m-d H:i:s') ?? 'N/A',
        ];
    }

    protected function mapDateInfo($letter): array
    {
        return [
            $letter->date_year ?? 'N/A',
            $letter->date_month ?? 'N/A',
            $letter->date_day ?? 'N/A',
            $this->boolToString($letter->date_marked),
            $this->boolToString($letter->date_uncertain),
            $this->boolToString($letter->date_approximate),
            $this->boolToString($letter->date_inferred),
            $this->boolToString($letter->date_is_range),
            $letter->date_note ?? 'N/A',
        ];
    }

    protected function mapIdentities($identities): array
    {
        $names = $identities->pluck('name')->implode('; ') ?? 'N/A';
        $notes = $identities->pluck('pivot.marked')->implode('; ') ?? 'N/A';
        $salutations = $identities->pluck('pivot.salutation')->implode('; ') ?? 'N/A';

        return [$names, $notes, $salutations];
    }

    protected function mapPlaces($places): array
    {
        $names = $places->pluck('name')->implode('; ') ?? 'N/A';
        $notes = $places->pluck('pivot.marked')->implode('; ') ?? 'N/A';

        return [$names, $notes];
    }

    protected function processKeywords($keywords): string
    {
        return $keywords->pluck('keyword_name')->implode('; ') ?? 'N/A';
    }

    protected function processCopies($copies): array
    {
        if (!$this->isValidJson($copies)) {
            return array_fill(0, 7, 'N/A');
        }

        $copiesData = json_decode($copies, true);

        if (empty($copiesData) || !is_array($copiesData)) {
            return array_fill(0, 7, 'N/A');
        }

        $copy = $copiesData[0] ?? [];
        return [
            $copy['ms_manifestation'] ?? 'N/A',
            $copy['type'] ?? 'N/A',
            $copy['preservation'] ?? 'N/A',
            $copy['repository'] ?? 'N/A',
            $copy['archive'] ?? 'N/A',
            $copy['collection'] ?? 'N/A',
            $copy['signature'] ?? 'N/A',
        ];
    }

    protected function processRelatedResources($resources): array
    {
        if (!$this->isValidJson($resources)) {
            return ['N/A', 'N/A'];
        }

        $resourcesData = json_decode($resources, true);

        if (empty($resourcesData) || !is_array($resourcesData)) {
            return ['N/A', 'N/A'];
        }

        $resource = $resourcesData[0] ?? [];
        return [
            $resource['title'] ?? 'N/A',
            $resource['link'] ?? 'N/A',
        ];
    }

    protected function mapContentSummary($letter): array
    {
        return [
            $letter->explicit ?? 'N/A',
            $letter->incipit ?? 'N/A',
            $this->summarize($letter->content, 50),
            $this->getAbstract($letter, 'cs'),
            $this->getAbstract($letter, 'en'),
            $this->formatLanguages($letter->languages),
            $letter->notes_private ?? 'N/A',
            $letter->notes_public ?? 'N/A',
            $letter->status ?? 'N/A',
            $this->wrapText($letter->history ?? 'N/A'),
        ];
    }

    protected function getAbstract($letter, string $language): string
    {
        return $letter->abstract[$language] ?? 'N/A';
    }

    protected function summarize(?string $text, int $limit): string
    {
        return $text ? (strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text) : 'N/A';
    }

    protected function formatLanguages(?string $languages): string
    {
        return $languages ? implode(', ', explode(';', $languages)) : 'N/A';
    }

    protected function boolToString($value): string
    {
        return $value ? 'Yes' : 'No';
    }

    protected function wrapText(string $text): string
    {
        return '"' . str_replace('"', '""', $text) . '"';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:D1'); // General Information
        $sheet->mergeCells('E1:P1'); // Date Details
        $sheet->mergeCells('Q1:S1'); // Authors
        $sheet->mergeCells('T1:V1'); // Recipients
        $sheet->mergeCells('W1:X1'); // Origins
        $sheet->mergeCells('Y1:Z1'); // Destinations
        $sheet->mergeCells('AA1:AA1'); // Keywords
        $sheet->mergeCells('AB1:AC1'); // Related Resources
        $sheet->mergeCells('AD1:AN1'); // Copies Metadata
        $sheet->mergeCells('AO1:AY1'); // Content Summary

        $sheet->getStyle('A1:AY1')->getFont()->setBold(true);
        $sheet->getStyle('A2:AY2')->getFont()->setBold(true);
        $sheet->getStyle('A1:AY1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1:AY1')->getAlignment()->setVertical('center');

        return [];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    protected function isValidJson($string): bool
    {
        if (is_string($string)) {
            json_decode($string);
            return json_last_error() === JSON_ERROR_NONE;
        }
        return false;
    }
}
