<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportSheetExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /** @param list<string> $headings */
    /** @param list<list<mixed>> $rows */
    public function __construct(
        private readonly string $title,
        private readonly array $headings,
        private readonly array $rows,
    ) {}

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }

    /** @return list<string> */
    public function headings(): array
    {
        return $this->headings;
    }

    /** @return list<list<mixed>> */
    public function array(): array
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '4338CA'],
                ],
            ],
        ];
    }
}
