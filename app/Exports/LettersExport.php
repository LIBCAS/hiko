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
    /**
     * Define the query to retrieve letters with necessary relationships.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return Letter::query()->with([
            'authors',
            'recipients',
            'origins',
            'destinations',
            'keywords',
        ])->select(
            'id',
            'uuid',
            'created_at',
            'updated_at',
            'history',
            'copies',
            'date_year',
            'date_month',
            'date_day',
            'date_computed',
            'status',
            'approval' // Include 'approval' in the select statement
        );
    }

    /**
     * Map each letter to an array representing a row in the Excel sheet.
     *
     * @param \App\Models\Letter $letter
     * @return array
     */
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
            $letter->approval ? __('hiko.approved') : __('hiko.not_approved'),
            $this->wrapText($letter->history ?? ''),
        ];
    }

    /**
     * Register events to style the Excel sheet, particularly headers and sub-headers.
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Define main headers with their respective merged cell ranges
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

                // Apply main headers and merge cells
                foreach ($headers as $header) {
                    [$title, $range] = $header;
                    preg_match('/([A-Z]+)1/', $range, $matches);
                    $cell = $matches[1] . '1';
                    $sheet->setCellValue($cell, $title);
                    $sheet->mergeCells($range);
                }

                // Define sub-headers for the second row
                $subHeaders = [
                    // General Information
                    'ID', 'UUID', 'Created At', 'Updated At',

                    // Date Information
                    'Date Year', 'Date Month', 'Date Day', 'Date Marked', 'Date Uncertain', 'Date Approximate', 'Date Inferred', 'Date Is Range', 'Date Note',

                    // Authors
                    'Author Names', 'Author Notes', 'Author Salutations',

                    // Recipients
                    'Recipient Names', 'Recipient Notes', 'Recipient Salutations',

                    // Origins
                    'Origin Places', 'Origin Notes',

                    // Destinations
                    'Destination Places', 'Destination Notes',

                    // Keywords
                    'Keyword List',

                    // Related Resources
                    'Related Resource Titles', 'Related Resource Links',

                    // Copies
                    'MS Manifestation (EMLO)', 'Document Type', 'Preservation', 'Type', 'Manifestation Note', 'Letter Number', 'Repository', 'Archive', 'Collection', 'Shelfmark', 'Preservation Location Note',

                    // Content and Notes
                    'Explicit', 'Incipit', 'Content (Summary)', 'Abstract CS', 'Abstract EN', 'Languages', 'Notes Private', 'Notes Public', 'Status', 'Approval', 'History' // **Added 'Approval'**
                ];

                // Populate sub-headers in the second row
                foreach ($subHeaders as $index => $subHeader) {
                    $column = $this->getColumnName($index);
                    $cell = $column . '2';
                    $sheet->setCellValue($cell, $subHeader);
                }

                // Apply styles to headers
                $sheet->getStyle('A1:AV2')->getFont()->setBold(true);
                $sheet->getStyle('A1:AV2')->getAlignment()->setHorizontal('center')->setVertical('center');
                $sheet->getStyle('A1:AV2')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Freeze panes to keep headers visible during scroll
                $sheet->freezePane('A3');
            },
        ];
    }

    /**
     * Apply additional styles to the worksheet.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Example: Bold the first two rows (headers)
        $sheet->getStyle('A1:AV2')->getFont()->setBold(true);
        return [];
    }

    /**
     * Define the chunk size for efficient memory usage during export.
     *
     * @return int
     */
    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Process related resources to extract titles and links.
     *
     * @param mixed $resources
     * @return array
     */
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

    /**
     * Process copies to extract relevant information.
     *
     * @param mixed $copies
     * @return array
     */
    protected function processCopies($copies): array
    {
        if (is_string($copies)) {
            $copies = json_decode($copies, true);
        }

        if (empty($copies)) {
            // Assuming there are 11 copy-related columns
            return array_fill(0, 11, '');
        }

        $copy = $copies[0] ?? []; // Take the first copy for export

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
            $copy['shelfmark'] ?? '',
            $copy['preservation_location_note'] ?? '',
        ];
    }

    /**
     * Convert boolean values to 'Yes' or 'No' strings.
     *
     * @param mixed $value
     * @return string
     */
    protected function boolToString($value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
    }

    /**
     * Summarize text to a specified character limit.
     *
     * @param string|null $text
     * @param int $limit
     * @return string
     */
    protected function summarize(?string $text, int $limit): string
    {
        return $text ? (mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . '...' : $text) : '';
    }

    /**
     * Retrieve the abstract in a specified language.
     *
     * @param \App\Models\Letter $letter
     * @param string $language
     * @return string
     */
    protected function getAbstract($letter, string $language): string
    {
        return $letter->getTranslation('abstract', $language) ?? '';
    }

    /**
     * Format languages from a semicolon-separated string to a comma-separated string.
     *
     * @param string|null $languages
     * @return string
     */
    protected function formatLanguages(?string $languages): string
    {
        return $languages ? implode(', ', explode(';', $languages)) : '';
    }

    /**
     * Wrap text with double quotes and escape existing double quotes.
     *
     * @param string $text
     * @return string
     */
    protected function wrapText(string $text): string
    {
        return '"' . str_replace('"', '""', $text) . '"';
    }

    /**
     * Format names from a collection of related models.
     *
     * @param \Illuminate\Support\Collection $items
     * @return string
     */
    protected function formatNames($items): string
    {
        return $items->pluck('name')->filter()->implode('; ');
    }

    /**
     * Format pivot fields from related models.
     *
     * @param \Illuminate\Support\Collection $items
     * @param string $field
     * @return string
     */
    protected function formatPivotField($items, string $field): string
    {
        return $items->pluck("pivot.$field")->filter()->implode('; ');
    }

    /**
     * Process keywords to a semicolon-separated string.
     *
     * @param \Illuminate\Support\Collection $keywords
     * @return string
     */
    protected function processKeywords($keywords): string
    {
        return $keywords->pluck('keyword_name')->filter()->implode('; ');
    }

    /**
     * Get the column name based on the index (0-based).
     * Handles columns beyond 'Z' by generating double letters (e.g., AA, AB).
     *
     * @param int $index
     * @return string
     */
    protected function getColumnName(int $index): string
    {
        $index += 1; // Adjust for 1-based indexing
        $columnName = '';

        while ($index > 0) {
            $modulo = ($index - 1) % 26;
            $columnName = chr(65 + $modulo) . $columnName;
            $index = (int)(($index - $modulo) / 26);
        }

        return $columnName;
    }
}
