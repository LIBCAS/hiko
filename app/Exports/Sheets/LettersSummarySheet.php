<?php

namespace App\Exports\Sheets;

use App\Services\LetterFilterService;
use DateTimeInterface;
use Generator;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LettersSummarySheet implements FromGenerator, WithEvents, WithHeadings, WithStyles, WithTitle
{
    private const SCALAR_COLUMN_COUNT = 12;

    private const GROUPS = [
        ['General Information', 4],
        ['Date Information', 3],
        ['Letter Overview', 5],
        ['Authors', 3],
        ['Recipients', 3],
        ['Mentioned', 2],
        ['Origins', 2],
        ['Destinations', 2],
        ['Keywords', 3],
        ['Manifestations', 10],
        ['Related Resources', 2],
    ];

    private array $letterRowRanges = [];

    public function __construct(private array $filters)
    {
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function headings(): array
    {
        $groupHeadings = [];

        foreach (self::GROUPS as [$title, $width]) {
            $groupHeadings[] = $title;
            array_push($groupHeadings, ...array_fill(0, $width - 1, ''));
        }

        return [
            $groupHeadings,
            [
                'ID', 'UUID', 'Created At', 'Updated At',
                'Date Computed', 'Date Marked', 'Date Note',
                'Abstract CS', 'Abstract EN', 'Languages', 'Status', 'Approval',
                'Name', 'Marked / Note', 'Salutation',
                'Name', 'Marked / Note', 'Salutation',
                'Name', 'Marked / Note',
                'Name', 'Marked / Note',
                'Name', 'Marked / Note',
                'Name CS', 'Name EN', 'Category',
                'Signature', 'Document Type', 'Preservation', 'Mode of Production', 'Letter Number',
                'Repository', 'Archive', 'Collection', 'Manifestation Note', 'Location Note',
                'Title', 'Link',
            ],
        ];
    }

    public function generator(): Generator
    {
        $this->letterRowRanges = [];
        $currentRow = 3;
        $relations = [
            'localIdentities', 'globalIdentities',
            'localPlaces', 'globalPlaces',
            'localKeywords.keyword_category', 'globalKeywords.keyword_category',
            'manifestations.repository', 'manifestations.archive', 'manifestations.collection',
            'manifestations.globalRepository', 'manifestations.globalArchive', 'manifestations.globalCollection',
        ];

        $letters = app(LetterFilterService::class)
            ->filteredQuery($this->filters, $relations)
            ->lazyById(500);

        foreach ($letters as $letter) {
            $authors = $this->identities($letter, 'author');
            $recipients = $this->identities($letter, 'recipient');
            $mentioned = $this->identities($letter, 'mentioned');
            $origins = $this->places($letter, 'origin');
            $destinations = $this->places($letter, 'destination');
            $keywords = $this->keywords($letter);
            $manifestations = $this->manifestations($letter);
            $resources = $this->resources($letter);
            $height = max(
                1,
                $authors->count(),
                $recipients->count(),
                $mentioned->count(),
                $origins->count(),
                $destinations->count(),
                $keywords->count(),
                $manifestations->count(),
                $resources->count()
            );

            $this->letterRowRanges[] = [$currentRow, $currentRow + $height - 1];

            for ($index = 0; $index < $height; $index++) {
                yield [
                    ...($index === 0 ? $this->scalarValues($letter) : array_fill(0, self::SCALAR_COLUMN_COUNT, null)),
                    ...($authors->get($index) ?? array_fill(0, 3, null)),
                    ...($recipients->get($index) ?? array_fill(0, 3, null)),
                    ...array_slice($mentioned->get($index) ?? array_fill(0, 3, null), 0, 2),
                    ...($origins->get($index) ?? array_fill(0, 2, null)),
                    ...($destinations->get($index) ?? array_fill(0, 2, null)),
                    ...($keywords->get($index) ?? array_fill(0, 3, null)),
                    ...($manifestations->get($index) ?? array_fill(0, 10, null)),
                    ...($resources->get($index) ?? array_fill(0, 2, null)),
                ];
            }

            $currentRow += $height;
        }
    }

    private function scalarValues(object $letter): array
    {
        return [
            $letter->id,
            $letter->uuid,
            $this->dateTime($letter->created_at),
            $this->dateTime($letter->updated_at),
            $this->dateTime($letter->date_computed),
            $letter->date_marked,
            $letter->date_note,
            $this->translation($letter, 'abstract', 'cs'),
            $this->translation($letter, 'abstract', 'en'),
            $letter->languages,
            $letter->status,
            $this->boolean($letter->approval),
        ];
    }

    private function identities(object $letter, string $role)
    {
        return $letter->localIdentities
            ->map(fn ($identity) => ['scope' => 'local', 'identity' => $identity])
            ->concat($letter->globalIdentities->map(
                fn ($identity) => ['scope' => 'global', 'identity' => $identity]
            ))
            ->filter(fn ($row) => $row['identity']->pivot->role === $role)
            ->sortBy(fn ($row) => sprintf(
                '%020d-%s-%020d',
                $row['identity']->pivot->position ?? PHP_INT_MAX,
                $row['scope'],
                $row['identity']->id
            ))
            ->values()
            ->map(function ($row) {
                $identity = $row['identity'];
                return [
                    $identity->name,
                    $identity->pivot->marked,
                    $identity->pivot->salutation,
                ];
            });
    }

    private function places(object $letter, string $role)
    {
        return $letter->localPlaces
            ->map(fn ($place) => ['scope' => 'local', 'place' => $place])
            ->concat($letter->globalPlaces->map(
                fn ($place) => ['scope' => 'global', 'place' => $place]
            ))
            ->filter(fn ($row) => $row['place']->pivot->role === $role)
            ->sortBy(fn ($row) => sprintf(
                '%020d-%s-%020d',
                $row['place']->pivot->position ?? PHP_INT_MAX,
                $row['scope'],
                $row['place']->id
            ))
            ->values()
            ->map(fn ($row) => [
                $row['place']->name,
                $row['place']->pivot->marked,
            ]);
    }

    private function keywords(object $letter)
    {
        return $letter->localKeywords
            ->map(fn ($keyword) => ['scope' => 'local', 'keyword' => $keyword])
            ->concat($letter->globalKeywords->map(
                fn ($keyword) => ['scope' => 'global', 'keyword' => $keyword]
            ))
            ->sortBy(fn ($row) => sprintf('%s-%020d', $row['scope'], $row['keyword']->id))
            ->values()
            ->map(function ($row) {
                $keyword = $row['keyword'];
                $category = $keyword->keyword_category;

                return [
                    $this->translation($keyword, 'name', 'cs'),
                    $this->translation($keyword, 'name', 'en'),
                    $category ? $this->localizedName($category) : '',
                ];
            });
    }

    private function manifestations(object $letter)
    {
        return $letter->manifestations
            ->sortBy('id')
            ->values()
            ->map(function ($manifestation) {
                $repository = $this->location(
                    $manifestation->repository,
                    $manifestation->globalRepository
                );
                $archive = $this->location(
                    $manifestation->archive,
                    $manifestation->globalArchive
                );
                $collection = $this->location(
                    $manifestation->collection,
                    $manifestation->globalCollection
                );

                return [
                    $manifestation->signature,
                    $manifestation->type,
                    $manifestation->preservation,
                    $manifestation->copy,
                    $manifestation->l_number,
                    ...$repository,
                    ...$archive,
                    ...$collection,
                    $manifestation->manifestation_notes,
                    $manifestation->location_note,
                ];
            });
    }

    private function resources(object $letter)
    {
        return collect($letter->related_resources ?? [])->values()->map(function ($resource) {
            $resource = is_array($resource) ? $resource : ['value' => $resource];

            return [
                $resource['title'] ?? '',
                $resource['link'] ?? '',
            ];
        });
    }

    private function location(?object $local, ?object $global): array
    {
        $location = $local ?? $global;
        $scope = $local ? 'local' : ($global ? 'global' : '');

        return [$location ? "{$location->name} ({$scope})" : ''];
    }

    private function boolean(mixed $value): int
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    private function dateTime(mixed $value): string
    {
        return $value instanceof DateTimeInterface
            ? $value->format('Y-m-d H:i:s')
            : (string) ($value ?? '');
    }

    private function localizedName(object $model): string
    {
        return collect([
            $this->translation($model, 'name', 'cs'),
            $this->translation($model, 'name', 'en'),
        ])->filter()->unique()->implode(' / ');
    }

    private function translation(object $model, string $field, string $locale): string
    {
        return (string) ($model->getTranslation($field, $locale, false) ?? '');
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '374151'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            2 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = Coordinate::stringFromColumnIndex($this->columnCount());
                $sheet->freezePane('A3');

                $firstColumn = 1;
                foreach (self::GROUPS as [, $width]) {
                    $lastGroupColumn = $firstColumn + $width - 1;
                    $firstGroupColumnName = Coordinate::stringFromColumnIndex($firstColumn);
                    $lastGroupColumnName = Coordinate::stringFromColumnIndex($lastGroupColumn);
                    if ($width > 1) {
                        $sheet->mergeCells(sprintf(
                            '%s1:%s1',
                            $firstGroupColumnName,
                            $lastGroupColumnName
                        ));
                    }
                    $sheet->getStyle("{$firstGroupColumnName}1:{$lastGroupColumnName}1")
                        ->applyFromArray([
                            'borders' => [
                                'outline' => [
                                    'borderStyle' => Border::BORDER_MEDIUM,
                                    'color' => ['rgb' => 'FFFFFF'],
                                ],
                            ],
                        ]);
                    $firstColumn = $lastGroupColumn + 1;
                }

                foreach ($this->letterRowRanges as $blockIndex => [$firstRow, $lastRow]) {
                    if ($lastRow > $firstRow) {
                        for ($column = 1; $column <= self::SCALAR_COLUMN_COUNT; $column++) {
                            $columnName = Coordinate::stringFromColumnIndex($column);
                            $sheet->mergeCells("{$columnName}{$firstRow}:{$columnName}{$lastRow}");
                            $sheet->getStyle("{$columnName}{$firstRow}")
                                ->getAlignment()
                                ->setVertical(Alignment::VERTICAL_CENTER);
                        }
                    }

                    if ($blockIndex % 2 === 1) {
                        $sheet->getStyle("A{$firstRow}:{$lastColumn}{$lastRow}")
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setRGB('F3F4F6');
                    }

                    $sheet->getStyle("A{$lastRow}:{$lastColumn}{$lastRow}")
                        ->getBorders()
                        ->getBottom()
                        ->setBorderStyle(Border::BORDER_MEDIUM)
                        ->getColor()
                        ->setRGB('9CA3AF');
                }

                foreach ($this->columnWidths() as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }
            },
        ];
    }

    private function columnCount(): int
    {
        return array_sum(array_column(self::GROUPS, 1));
    }

    private function columnWidths(): array
    {
        return [
            'A' => 10, 'B' => 38, 'C' => 20, 'D' => 20,
            'G' => 35, 'H' => 35, 'I' => 35,
            'M' => 30, 'P' => 30, 'S' => 30,
            'U' => 30, 'W' => 30, 'Y' => 30, 'Z' => 30, 'AA' => 30,
            'AB' => 25, 'AG' => 30, 'AH' => 35, 'AI' => 35, 'AJ' => 35,
            'AK' => 40, 'AL' => 40, 'AM' => 60,
        ];
    }
}
