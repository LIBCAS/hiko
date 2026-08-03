<?php

namespace App\Exports\Sheets;

use App\Services\LetterFilterService;
use DateTimeInterface;
use Generator;
use Illuminate\Support\LazyCollection;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class AbstractLetterSheet implements FromGenerator, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    public function __construct(protected array $filters)
    {
    }

    abstract public function generator(): Generator;

    /**
     * Read filtered letters in bounded chunks while eager-loading only the
     * relationships needed by the current sheet.
     */
    protected function letters(array $with = []): LazyCollection
    {
        return app(LetterFilterService::class)
            ->filteredQuery($this->filters, $with)
            ->lazyById(500);
    }

    protected function boolean(mixed $value): int
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    protected function dateTime(mixed $value): string
    {
        return $value instanceof DateTimeInterface
            ? $value->format('Y-m-d H:i:s')
            : (string) ($value ?? '');
    }

    protected function json(mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }

    protected function translation(object $model, string $field, string $locale): string
    {
        return (string) ($model->getTranslation($field, $locale, false) ?? '');
    }

    protected function reference(string $scope, mixed $id): string
    {
        return $id ? "{$scope}-{$id}" : '';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4B5563'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');
                $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
                $sheet->getStyle($sheet->calculateWorksheetDimension())
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_TOP);
            },
        ];
    }
}
