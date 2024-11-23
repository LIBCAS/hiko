<?php

namespace App\Exports;

use App\Models\Letter;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LettersExport implements FromQuery, WithMapping, WithEvents, WithStyles, WithChunkReading, ShouldAutoSize
{
    public function query()
    {
        return Letter::query()->with([
            'authors',
            'recipients',
            'origins',
            'destinations',
            'keywords',
        ]);
    }

    public function map($letter): array
    {
        // Related Resources split into titles and links
        list($relatedResourceTitles, $relatedResourceLinks) = $this->processRelatedResources($letter->related_resources);

        // Copies processed
        $copies = $this->processCopies($letter->copies);

        return [
            // General Information
            $letter->id,
            $letter->uuid,
            $letter->created_at ? $letter->created_at->format('Y-m-d H:i:s') : '',
            $letter->updated_at ? $letter->updated_at->format('Y-m-d H:i:s') : '',

            // Date Information
            $letter->date_year ?? '',
            $letter->date_month ?? '',
            $letter->date_day ?? '',
            $this->boolToString($letter->date_marked),
            $this->boolToString($letter->date_uncertain),
            $this->boolToString($letter->date_approximate),
            $this->boolToString($letter->date_inferred),
            $this->boolToString($letter->date_is_range),
            $letter->date_note ?? '',

            // Authors
            $this->formatNames($letter->authors),
            $this->formatPivotField($letter->authors, 'marked'),
            $this->formatPivotField($letter->authors, 'salutation'),

            // Recipients
            $this->formatNames($letter->recipients),
            $this->formatPivotField($letter->recipients, 'marked'),
            $this->formatPivotField($letter->recipients, 'salutation'),

            // Origins
            $this->formatNames($letter->origins),
            $this->formatPivotField($letter->origins, 'marked'),

            // Destinations
            $this->formatNames($letter->destinations),
            $this->formatPivotField($letter->destinations, 'marked'),

            // Keywords
            $this->processKeywords($letter->keywords),

            // Related Resources
            $relatedResourceTitles,
            $relatedResourceLinks,

            // Copies
            ...$copies,

            // Content and Notes
            $letter->explicit ?? '',
            $letter->incipit ?? '',
            $this->summarize($letter->content, 50),
            $this->getAbstract($letter, 'cs') ?: '',
            $this->getAbstract($letter, 'en') ?: '',
            $this->formatLanguages($letter->languages),
            $letter->notes_private ?? '',
            $letter->notes_public ?? '',
            $letter->status ?? '',
            $this->wrapText($letter->history ?? ''),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Group Headers
                $headers = [
                    ['General Information', 'A1:D1'],
                    ['Date Information', 'E1:M1'],
                    ['Authors', 'N1:P1'],
                    ['Recipients', 'Q1:S1'],
                    ['Origins', 'T1:U1'],
                    ['Destinations', 'V1:W1'],
                    ['Keywords', 'X1:X1'],
                    ['Related Resources', 'Y1:Z1'],
                    ['Copies', 'AA1:AK1'],
                    ['Content and Notes', 'AL1:AV1'],
                ];

                // Correctly apply headers to the sheet
                foreach ($headers as $header) {
                    [$title, $range] = $header;
                    preg_match('/([A-Z]+)1/', $range, $matches);
                    $cell = $matches[1] . '1';
                    $sheet->setCellValue($cell, $title);
                    $sheet->mergeCells($range);
                }

                // Sub-Headers (Second row)
                $subHeaders = [
                    'ID', 'UUID', 'Created At', 'Updated At',
                    'Date Year', 'Date Month', 'Date Day', 'Date Marked', 'Date Uncertain', 'Date Approximate', 'Date Inferred', 'Date Is Range', 'Date Note',
                    'Author Names', 'Author Notes', 'Author Salutations',
                    'Recipient Names', 'Recipient Notes', 'Recipient Salutations',
                    'Origin Places', 'Origin Notes',
                    'Destination Places', 'Destination Notes',
                    'Keyword List',
                    'Related Resource Titles', 'Related Resource Links',
                    'MS Manifestation (EMLO)', 'Document Type', 'Preservation', 'Type', 'Manifestation Note', 'Letter Number', 'Repository', 'Archive', 'Collection', 'Shelfmark', 'Preservation Location Note',
                    'Explicit', 'Incipit', 'Content (Summary)', 'Abstract CS', 'Abstract EN', 'Languages', 'Notes Private', 'Notes Public', 'Status', 'History'
                ];

                foreach ($subHeaders as $index => $subHeader) {
                    $column = chr(65 + $index);
                    if ($index >= 26) {
                        // For columns beyond 'Z', generate double letters 'AA', 'AB', etc.
                        $column = 'A' . chr(65 + ($index - 26));
                    }
                    $cell = $column . '2';
                    $sheet->setCellValue($cell, $subHeader);
                }

                // Apply styles to headers
                $sheet->getStyle('A1:AV2')->getFont()->setBold(true);
                $sheet->getStyle('A1:AV2')->getAlignment()->setHorizontal('center')->setVertical('center');
                $sheet->getStyle('A1:AV2')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Freeze panes to keep headers visible
                $sheet->freezePane('A3');
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:AV1')->getFont()->setBold(true);
        return [];
    }

    public function chunkSize(): int
    {
        return 1000; // Implementing the missing chunkSize method
    }

    // Helper methods...
    protected function processRelatedResources($resources): array
    {
        if (is_string($resources)) {
            $resources = json_decode($resources, true);
        }

        if (empty($resources)) {
            return ['', ''];
        }

        $titles = collect($resources)->pluck('title')->filter()->implode('; ');
        $links = collect($resources)->pluck('link')->filter()->implode('; ');

        return [$titles, $links];
    }

    protected function processCopies($copies): array
    {
        if (is_string($copies)) {
            $copies = json_decode($copies, true);
        }

        if (empty($copies)) {
            return array_fill(0, 11, '');
        }

        $copy = $copies[0] ?? [];

        return [
            $copy['ms_manifestation'] ?? '',
            $copy['document_type'] ?? '',
            $copy['preservation'] ?? '',
            $copy['type'] ?? '',
            $copy['manifestation_notes'] ?? '',
            $copy['l_number'] ?? '',
            $copy['repository'] ?? '',
            $copy['archive'] ?? '',
            $copy['collection'] ?? '',
            $copy['signature'] ?? '',
            $copy['location_note'] ?? '',
        ];
    }

    protected function boolToString($value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
    }

    protected function summarize(?string $text, int $limit): string
    {
        return $text ? (mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . '...' : $text) : '';
    }

    protected function getAbstract($letter, string $language): string
    {
        return $letter->getTranslation('abstract', $language) ?? '';
    }

    protected function formatLanguages(?string $languages): string
    {
        return $languages ? implode(', ', explode(';', $languages)) : '';
    }

    protected function wrapText(string $text): string
    {
        return '"' . str_replace('"', '""', $text) . '"';
    }

    protected function formatNames($items): string
    {
        return $items->pluck('name')->filter()->implode('; ');
    }

    protected function formatPivotField($items, string $field): string
    {
        return $items->pluck("pivot.$field")->filter()->implode('; ');
    }

    protected function processKeywords($keywords): string
    {
        return $keywords->pluck('keyword_name')->filter()->implode('; ');
    }
}
