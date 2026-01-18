<?php

namespace App\Exports;

use App\Models\Account;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LedgerExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $transactions;
    protected $account;
    protected $openingBalance;
    protected $closingBalance;
    protected $dateFrom;
    protected $dateTo;

    public function __construct(array $transactions, Account $account, float $openingBalance, float $closingBalance, string $dateFrom, string $dateTo)
    {
        $this->transactions = $transactions;
        $this->account = $account;
        $this->openingBalance = $openingBalance;
        $this->closingBalance = $closingBalance;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function array(): array
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Reference',
            'Description',
            'Narration',
            'Debit',
            'Credit',
            'Balance',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Ledger - ' . $this->account->code;
    }
}
