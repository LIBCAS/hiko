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

class LettersExport implements FromQuery, WithMapping, WithHeadings, WithStyles, WithChunkReading, ShouldAutoSize
{
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

    public function headings(): array
    {
        return [
            [
                'General Information', '', '', '',
                'Date Information', '', '', '', '', '', '', '',
                'Authors', '', '',
                'Recipients', '', '',
                'Origins', '', '',
                'Destinations', '', '',
                'Keywords', '',
                'Related Resources', '',
                'Copies', '', '', '', '', '', '', '', '', '',
                'Content and Notes', '', '', '', '', '', '', '', '', ''
            ],
            [
                'ID', 'UUID', 'Created At', 'Updated At',
                'Date Year', 'Date Month', 'Date Day', 'Date Marked',
                'Date Uncertain', 'Date Approximate', 'Date Inferred',
                'Date Is Range', 'Date Note',
                'Author Names', 'Author Notes', 'Author Salutations',
                'Recipient Names', 'Recipient Notes', 'Recipient Salutations',
                'Origin Places', 'Origin Notes',
                'Destination Places', 'Destination Notes',
                'Keyword List',
                'Related Resource Names', 'Related Resource URLs',
                'MS Manifestation (EMLO)', 'Document Type', 'Preservation', 'Type',
                'Manifestation Note', 'Letter Number', 'Repository', 'Archive',
                'Collection', 'Shelfmark', 'Preservation Location Note',
                'Explicit', 'Incipit', 'Content (Summary)', 'Abstract CS', 'Abstract EN',
                'Languages', 'Notes Private', 'Notes Public', 'Status', 'History'
            ]
        ];
    }

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
            $this->mapContentAndNotes($letter)
        );
    }

    protected function mapGeneralInfo($letter): array
    {
        return [
            $letter->id,
            $letter->uuid,
            $letter->created_at->format('Y-m-d H:i:s'),
            $letter->updated_at->format('Y-m-d H:i:s'),
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

    protected function processRelatedResources($resources): array
    {
        if (is_string($resources)) {
            $resources = json_decode($resources, true);
        }

        if (empty($resources)) {
            return ['N/A', 'N/A'];
        }

        $resource = $resources[0] ?? [];
        return [
            $resource['name'] ?? 'N/A',
            $resource['url'] ?? 'N/A',
        ];
    }

    protected function processCopies($copies): array
    {
        if (is_string($copies)) {
            $copies = json_decode($copies, true);
        }

        if (empty($copies)) {
            return array_fill(0, 11, 'N/A');
        }

        $copy = $copies[0] ?? [];
        return [
            $copy['ms_manifestation'] ?? 'N/A',
            $copy['document_type'] ?? 'N/A',
            $copy['preservation'] ?? 'N/A',
            $copy['type'] ?? 'N/A',
            $copy['manifestation_notes'] ?? 'N/A',
            $copy['l_number'] ?? 'N/A',
            $copy['repository'] ?? 'N/A',
            $copy['archive'] ?? 'N/A',
            $copy['collection'] ?? 'N/A',
            $copy['signature'] ?? 'N/A',
            $copy['location_note'] ?? 'N/A',
        ];
    }

    protected function mapContentAndNotes($letter): array
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
        $sheet->mergeCells('A1:D1');
        $sheet->mergeCells('E1:P1');
        $sheet->mergeCells('Q1:S1');
        $sheet->mergeCells('T1:V1');
        $sheet->mergeCells('W1:X1');
        $sheet->mergeCells('Y1:Z1');
        $sheet->mergeCells('AA1:AA1');
        $sheet->mergeCells('AB1:AC1');
        $sheet->mergeCells('AD1:AN1');
        $sheet->mergeCells('AO1:AY1');

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
}
