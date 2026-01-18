<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashflowExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $data;
    protected $dateFrom;
    protected $dateTo;

    public function __construct(array $data, string $dateFrom, string $dateTo)
    {
        $this->data = $data;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Item',
            'Amount',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            5 => ['font' => ['bold' => true]], // Net Cashflow row
        ];
    }

    public function title(): string
    {
        return 'Cash Flow Statement';
    }
}
