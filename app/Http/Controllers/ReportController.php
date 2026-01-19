<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Enums\JournalEntryStatus;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function ledger(Request $request): Response
    {
        $accountId = $request->get('account_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $transactions = collect();
        $openingBalance = 0;
        $runningBalance = 0;

        if ($accountId && $dateFrom && $dateTo) {
            $account = Account::findOrFail($accountId);

            $query = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $accountId)
                ->whereBetween('journal_entries.entry_date', [$dateFrom, $dateTo])
                ->orderBy('journal_entries.entry_date')
                ->orderBy('journal_entries.id')
                ->orderBy('journal_lines.line_no')
                ->select([
                    'journal_entries.id as entry_id',
                    'journal_entries.entry_date',
                    'journal_entries.reference',
                    'journal_entries.description',
                    'journal_lines.narration',
                    'journal_lines.debit',
                    'journal_lines.credit',
                ]);

            $beforeDateTransactions = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $accountId)
                ->where('journal_entries.entry_date', '<', $dateFrom)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            if ($beforeDateTransactions) {
                if ($account->normal_balance->value === 'debit') {
                    $openingBalance = ($beforeDateTransactions->total_debit ?? 0) - ($beforeDateTransactions->total_credit ?? 0);
                } else {
                    $openingBalance = ($beforeDateTransactions->total_credit ?? 0) - ($beforeDateTransactions->total_debit ?? 0);
                }
            }

            $runningBalance = $openingBalance;

            $transactions = $query->get()->map(function ($transaction) use ($account, &$runningBalance) {
                if ($account->normal_balance->value === 'debit') {
                    $runningBalance += $transaction->debit - $transaction->credit;
                } else {
                    $runningBalance += $transaction->credit - $transaction->debit;
                }

                return [
                    'entry_id' => $transaction->entry_id,
                    'entry_date' => $transaction->entry_date,
                    'reference' => $transaction->reference,
                    'description' => $transaction->description,
                    'narration' => $transaction->narration,
                    'debit' => $transaction->debit,
                    'credit' => $transaction->credit,
                    'balance' => $runningBalance,
                ];
            });
        }

        $accounts = Account::where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'label' => "{$account->code} - {$account->name}",
            ]);

        return Inertia::render('Reports/Ledger', [
            'transactions' => $transactions,
            'openingBalance' => $openingBalance,
            'closingBalance' => $runningBalance,
            'accounts' => $accounts,
            'filters' => $request->only(['account_id', 'date_from', 'date_to']),
        ]);
    }

    public function trialBalance(Request $request): Response
    {
        $asAtDate = $request->get('as_at_date', now()->toDateString());

        $accounts = Account::where('is_active', true)->get();

        $balances = JournalLine::query()
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.status', JournalEntryStatus::POSTED)
            ->where('journal_entries.entry_date', '<=', $asAtDate)
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type', 'accounts.normal_balance')
            ->selectRaw('accounts.id, accounts.code, accounts.name, accounts.type, accounts.normal_balance, SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
            ->get();

        $trialBalance = $accounts->map(function ($account) use ($balances) {
            $balance = $balances->firstWhere('id', $account->id);

            $debit = $balance->total_debit ?? 0;
            $credit = $balance->total_credit ?? 0;

            if ($account->normal_balance->value === 'debit') {
                $netBalance = $debit - $credit;
                $debitBalance = $netBalance > 0 ? $netBalance : 0;
                $creditBalance = $netBalance < 0 ? abs($netBalance) : 0;
            } else {
                $netBalance = $credit - $debit;
                $debitBalance = $netBalance < 0 ? abs($netBalance) : 0;
                $creditBalance = $netBalance > 0 ? $netBalance : 0;
            }

            return [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type->value,
                'debit' => $debitBalance,
                'credit' => $creditBalance,
            ];
        })->filter(fn($item) => $item['debit'] > 0 || $item['credit'] > 0)
          ->sortBy('code')
          ->values();

        $totalDebit = $trialBalance->sum('debit');
        $totalCredit = $trialBalance->sum('credit');

        return Inertia::render('Reports/TrialBalance', [
            'trialBalance' => $trialBalance,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'asAtDate' => $asAtDate,
        ]);
    }

    public function incomeStatement(Request $request): Response
    {
        $dateFrom = $request->get('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        // Get individual revenue accounts
        $revenueAccounts = Account::where('type', AccountType::REVENUE)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $revenueItems = [];
        foreach ($revenueAccounts as $account) {
            $result = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->whereBetween('journal_entries.entry_date', [$dateFrom, $dateTo])
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $debit = (float) ($result->total_debit ?? 0);
            $credit = (float) ($result->total_credit ?? 0);

            // For revenue accounts (credit normal), amount is credit - debit
            $amount = $credit - $debit;

            if ($amount > 0) {
                $revenueItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }
        }

        // Get individual expense accounts
        $expenseAccounts = Account::where('type', AccountType::EXPENSE)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $expenseItems = [];
        foreach ($expenseAccounts as $account) {
            $result = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->whereBetween('journal_entries.entry_date', [$dateFrom, $dateTo])
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $debit = (float) ($result->total_debit ?? 0);
            $credit = (float) ($result->total_credit ?? 0);

            // For expense accounts (debit normal), amount is debit - credit
            $amount = $debit - $credit;

            if ($amount > 0) {
                $expenseItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }
        }

        $totalRevenue = collect($revenueItems)->sum('amount');
        $totalExpenses = collect($expenseItems)->sum('amount');
        $netSurplus = $totalRevenue - $totalExpenses;

        return Inertia::render('Reports/IncomeStatement', [
            'revenueItems' => $revenueItems,
            'expenseItems' => $expenseItems,
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'netSurplus' => $netSurplus,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function balanceSheet(Request $request): Response
    {
        $asAtDate = $request->get('as_at_date', now()->toDateString());

        // Get individual asset accounts
        $assetAccounts = Account::where('type', AccountType::ASSET)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $assetItems = [];
        foreach ($assetAccounts as $account) {
            $balance = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $asAtDate)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $debit = (float) ($balance->total_debit ?? 0);
            $credit = (float) ($balance->total_credit ?? 0);

            if ($account->normal_balance->value === 'debit') {
                $amount = $debit - $credit;
            } else {
                $amount = $credit - $debit;
            }

            if ($amount > 0) {
                $assetItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }
        }

        // Get individual liability accounts
        $liabilityAccounts = Account::where('type', AccountType::LIABILITY)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $liabilityItems = [];
        foreach ($liabilityAccounts as $account) {
            $balance = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $asAtDate)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $debit = (float) ($balance->total_debit ?? 0);
            $credit = (float) ($balance->total_credit ?? 0);

            if ($account->normal_balance->value === 'credit') {
                $amount = $credit - $debit;
            } else {
                $amount = $debit - $credit;
            }

            if ($amount > 0) {
                $liabilityItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }
        }

        // Get individual equity accounts
        $equityAccountRecords = Account::where('type', AccountType::EQUITY)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $equityItems = [];
        foreach ($equityAccountRecords as $account) {
            $balance = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $asAtDate)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $debit = (float) ($balance->total_debit ?? 0);
            $credit = (float) ($balance->total_credit ?? 0);

            if ($account->normal_balance->value === 'credit') {
                $amount = $credit - $debit;
            } else {
                $amount = $debit - $credit;
            }

            if ($amount > 0) {
                $equityItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }
        }

        // Calculate current surplus (revenue - expenses for the year)
        $dateFrom = now()->startOfYear()->toDateString();
        $revenue = $this->getAccountTypeTotal(AccountType::REVENUE, $dateFrom, $asAtDate);
        $expenses = $this->getAccountTypeTotal(AccountType::EXPENSE, $dateFrom, $asAtDate);
        $currentSurplus = $revenue - $expenses;

        // Calculate totals
        $totalAssets = collect($assetItems)->sum('amount');
        $totalLiabilities = collect($liabilityItems)->sum('amount');
        $totalEquityAccounts = collect($equityItems)->sum('amount');
        $totalEquity = $totalEquityAccounts + $currentSurplus;
        $totalLiabilitiesEquity = $totalLiabilities + $totalEquity;

        return Inertia::render('Reports/BalanceSheet', [
            'assetItems' => $assetItems,
            'liabilityItems' => $liabilityItems,
            'equityItems' => $equityItems,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquityAccounts' => $totalEquityAccounts,
            'currentSurplus' => $currentSurplus,
            'totalEquity' => $totalEquity,
            'totalLiabilitiesEquity' => $totalLiabilitiesEquity,
            'asAtDate' => $asAtDate,
        ]);
    }

    public function cashflow(Request $request): Response
    {
        $dateFrom = $request->get('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        // Calculate beginning date (day before the period starts)
        $beginningDate = date('Y-m-d', strtotime($dateFrom . ' -1 day'));

        $cashAccounts = Account::where('is_active', true)
            ->where('is_cash', true)
            ->get();

        $operating = 0;
        $investing = 0;
        $financing = 0;

        foreach ($cashAccounts as $account) {
            if (!$account->cashflow_type) {
                continue;
            }

            // Get beginning balance (up to day before period starts)
            $beginningResult = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $beginningDate)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $beginningDebit = (float) ($beginningResult->total_debit ?? 0);
            $beginningCredit = (float) ($beginningResult->total_credit ?? 0);

            // Get ending balance (up to end of period)
            $endingResult = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $dateTo)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $endingDebit = (float) ($endingResult->total_debit ?? 0);
            $endingCredit = (float) ($endingResult->total_credit ?? 0);

            // Calculate balances based on normal balance
            if ($account->normal_balance->value === 'debit') {
                $beginningBalance = $beginningDebit - $beginningCredit;
                $endingBalance = $endingDebit - $endingCredit;
            } else {
                $beginningBalance = $beginningCredit - $beginningDebit;
                $endingBalance = $endingCredit - $endingDebit;
            }

            // Calculate net change during the period
            $netChange = $endingBalance - $beginningBalance;

            // Add to appropriate category
            $type = $account->cashflow_type->value;
            switch ($type) {
                case 'operating':
                    $operating += $netChange;
                    break;
                case 'investing':
                    $investing += $netChange;
                    break;
                case 'financing':
                    $financing += $netChange;
                    break;
            }
        }

        $netCashflow = $operating + $investing + $financing;

        return Inertia::render('Reports/Cashflow', [
            'operating' => $operating,
            'investing' => $investing,
            'financing' => $financing,
            'netCashflow' => $netCashflow,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    private function getAccountTypeTotal(AccountType $type, string $dateFrom, string $dateTo): float
    {
        $result = JournalLine::query()
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.status', JournalEntryStatus::POSTED)
            ->where('accounts.type', $type->value)
            ->whereBetween('journal_entries.entry_date', [$dateFrom, $dateTo])
            ->selectRaw('SUM(CASE WHEN accounts.normal_balance = ? THEN journal_lines.credit ELSE journal_lines.debit END) as total', [$type->value === 'revenue' ? 'credit' : 'debit'])
            ->value('total');

        return (float) ($result ?? 0);
    }

    private function getAccountTypeBalance(AccountType $type, string $asAtDate): float
    {
        $accounts = Account::where('type', $type->value)->where('is_active', true)->get();

        $total = 0;

        foreach ($accounts as $account) {
            $balance = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $asAtDate)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $debit = (float) ($balance->total_debit ?? 0);
            $credit = (float) ($balance->total_credit ?? 0);

            if ($account->normal_balance->value === 'debit') {
                $total += $debit - $credit;
            } else {
                $total += $credit - $debit;
            }
        }

        return $total;
    }

    // PDF Export Methods
    public function trialBalancePdf(Request $request)
    {
        $asAtDate = $request->get('as_at_date', now()->toDateString());
        $accounts = Account::where('is_active', true)->get();

        $balances = JournalLine::query()
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.status', JournalEntryStatus::POSTED)
            ->where('journal_entries.entry_date', '<=', $asAtDate)
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type', 'accounts.normal_balance')
            ->selectRaw('accounts.id, accounts.code, accounts.name, accounts.type, accounts.normal_balance, SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
            ->get();

        $trialBalance = $accounts->map(function ($account) use ($balances) {
            $balance = $balances->firstWhere('id', $account->id);
            $debit = $balance->total_debit ?? 0;
            $credit = $balance->total_credit ?? 0;

            if ($account->normal_balance->value === 'debit') {
                $netBalance = $debit - $credit;
                $debitBalance = $netBalance > 0 ? $netBalance : 0;
                $creditBalance = $netBalance < 0 ? abs($netBalance) : 0;
            } else {
                $netBalance = $credit - $debit;
                $debitBalance = $netBalance < 0 ? abs($netBalance) : 0;
                $creditBalance = $netBalance > 0 ? $netBalance : 0;
            }

            return [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type->value,
                'debit' => $debitBalance,
                'credit' => $creditBalance,
            ];
        })->filter(fn($item) => $item['debit'] > 0 || $item['credit'] > 0)
          ->sortBy('code')
          ->values();

        $totalDebit = $trialBalance->sum('debit');
        $totalCredit = $trialBalance->sum('credit');

        $pdf = DomPDF::loadView('reports.trial-balance-pdf', [
            'trialBalance' => $trialBalance,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'asAtDate' => $asAtDate,
        ]);

        return $pdf->download('trial-balance-' . $asAtDate . '.pdf');
    }

    public function ledgerPdf(Request $request)
    {
        $accountId = $request->get('account_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$accountId || !$dateFrom || !$dateTo) {
            return redirect()->back()->withErrors(['error' => 'Account, date from, and date to are required']);
        }

        $account = Account::findOrFail($accountId);
        $query = JournalLine::query()
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', JournalEntryStatus::POSTED)
            ->where('journal_lines.account_id', $accountId)
            ->whereBetween('journal_entries.entry_date', [$dateFrom, $dateTo])
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_lines.line_no')
            ->select([
                'journal_entries.id as entry_id',
                'journal_entries.entry_date',
                'journal_entries.reference',
                'journal_entries.description',
                'journal_lines.narration',
                'journal_lines.debit',
                'journal_lines.credit',
            ]);

        $beforeDateTransactions = JournalLine::query()
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', JournalEntryStatus::POSTED)
            ->where('journal_lines.account_id', $accountId)
            ->where('journal_entries.entry_date', '<', $dateFrom)
            ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
            ->first();

        $openingBalance = 0;
        if ($beforeDateTransactions) {
            if ($account->normal_balance->value === 'debit') {
                $openingBalance = ($beforeDateTransactions->total_debit ?? 0) - ($beforeDateTransactions->total_credit ?? 0);
            } else {
                $openingBalance = ($beforeDateTransactions->total_credit ?? 0) - ($beforeDateTransactions->total_debit ?? 0);
            }
        }

        $runningBalance = $openingBalance;
        $transactions = $query->get()->map(function ($transaction) use ($account, &$runningBalance) {
            if ($account->normal_balance->value === 'debit') {
                $runningBalance += $transaction->debit - $transaction->credit;
            } else {
                $runningBalance += $transaction->credit - $transaction->debit;
            }

            return [
                'entry_id' => $transaction->entry_id,
                'entry_date' => $transaction->entry_date,
                'reference' => $transaction->reference,
                'description' => $transaction->description,
                'narration' => $transaction->narration,
                'debit' => $transaction->debit,
                'credit' => $transaction->credit,
                'balance' => $runningBalance,
            ];
        });

        $pdf = DomPDF::loadView('reports.ledger-pdf', [
            'account' => $account,
            'transactions' => $transactions,
            'openingBalance' => $openingBalance,
            'closingBalance' => $runningBalance,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);

        return $pdf->download('ledger-' . $account->code . '-' . $dateFrom . '-' . $dateTo . '.pdf');
    }

    public function incomeStatementPdf(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        // Get individual revenue accounts
        $revenueAccounts = Account::where('type', AccountType::REVENUE)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $revenueItems = [];
        foreach ($revenueAccounts as $account) {
            $result = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->whereBetween('journal_entries.entry_date', [$dateFrom, $dateTo])
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $debit = (float) ($result->total_debit ?? 0);
            $credit = (float) ($result->total_credit ?? 0);
            $amount = $credit - $debit;

            if ($amount > 0) {
                $revenueItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }
        }

        // Get individual expense accounts
        $expenseAccounts = Account::where('type', AccountType::EXPENSE)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $expenseItems = [];
        foreach ($expenseAccounts as $account) {
            $result = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->whereBetween('journal_entries.entry_date', [$dateFrom, $dateTo])
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $debit = (float) ($result->total_debit ?? 0);
            $credit = (float) ($result->total_credit ?? 0);
            $amount = $debit - $credit;

            if ($amount > 0) {
                $expenseItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }
        }

        $totalRevenue = collect($revenueItems)->sum('amount');
        $totalExpenses = collect($expenseItems)->sum('amount');
        $netSurplus = $totalRevenue - $totalExpenses;

        $pdf = DomPDF::loadView('reports.income-statement-pdf', [
            'revenueItems' => $revenueItems,
            'expenseItems' => $expenseItems,
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'netSurplus' => $netSurplus,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);

        return $pdf->download('income-statement-' . $dateFrom . '-' . $dateTo . '.pdf');
    }

    public function balanceSheetPdf(Request $request)
    {
        $asAtDate = $request->get('as_at_date', now()->toDateString());

        // Get individual asset accounts
        $assetAccounts = Account::where('type', AccountType::ASSET)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $assetItems = [];
        foreach ($assetAccounts as $account) {
            $balance = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $asAtDate)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $debit = (float) ($balance->total_debit ?? 0);
            $credit = (float) ($balance->total_credit ?? 0);

            if ($account->normal_balance->value === 'debit') {
                $amount = $debit - $credit;
            } else {
                $amount = $credit - $debit;
            }

            if ($amount > 0) {
                $assetItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }
        }

        // Get individual liability accounts
        $liabilityAccounts = Account::where('type', AccountType::LIABILITY)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $liabilityItems = [];
        foreach ($liabilityAccounts as $account) {
            $balance = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $asAtDate)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $debit = (float) ($balance->total_debit ?? 0);
            $credit = (float) ($balance->total_credit ?? 0);

            if ($account->normal_balance->value === 'credit') {
                $amount = $credit - $debit;
            } else {
                $amount = $debit - $credit;
            }

            if ($amount > 0) {
                $liabilityItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }
        }

        // Get individual equity accounts
        $equityAccountRecords = Account::where('type', AccountType::EQUITY)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $equityItems = [];
        foreach ($equityAccountRecords as $account) {
            $balance = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $asAtDate)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $debit = (float) ($balance->total_debit ?? 0);
            $credit = (float) ($balance->total_credit ?? 0);

            if ($account->normal_balance->value === 'credit') {
                $amount = $credit - $debit;
            } else {
                $amount = $debit - $credit;
            }

            if ($amount > 0) {
                $equityItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }
        }

        // Calculate current surplus
        $dateFrom = now()->startOfYear()->toDateString();
        $revenue = $this->getAccountTypeTotal(AccountType::REVENUE, $dateFrom, $asAtDate);
        $expenses = $this->getAccountTypeTotal(AccountType::EXPENSE, $dateFrom, $asAtDate);
        $currentSurplus = $revenue - $expenses;

        // Calculate totals
        $totalAssets = collect($assetItems)->sum('amount');
        $totalLiabilities = collect($liabilityItems)->sum('amount');
        $totalEquityAccounts = collect($equityItems)->sum('amount');
        $totalEquity = $totalEquityAccounts + $currentSurplus;
        $totalLiabilitiesEquity = $totalLiabilities + $totalEquity;

        $pdf = DomPDF::loadView('reports.balance-sheet-pdf', [
            'assetItems' => $assetItems,
            'liabilityItems' => $liabilityItems,
            'equityItems' => $equityItems,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquityAccounts' => $totalEquityAccounts,
            'currentSurplus' => $currentSurplus,
            'totalEquity' => $totalEquity,
            'totalLiabilitiesEquity' => $totalLiabilitiesEquity,
            'asAtDate' => $asAtDate,
        ]);

        return $pdf->download('balance-sheet-' . $asAtDate . '.pdf');
    }

    public function cashflowPdf(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        $beginningDate = date('Y-m-d', strtotime($dateFrom . ' -1 day'));
        $cashAccounts = Account::where('is_active', true)->where('is_cash', true)->get();

        $operating = 0;
        $investing = 0;
        $financing = 0;

        foreach ($cashAccounts as $account) {
            if (!$account->cashflow_type) {
                continue;
            }

            $beginningResult = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $beginningDate)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $beginningDebit = (float) ($beginningResult->total_debit ?? 0);
            $beginningCredit = (float) ($beginningResult->total_credit ?? 0);

            $endingResult = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $dateTo)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $endingDebit = (float) ($endingResult->total_debit ?? 0);
            $endingCredit = (float) ($endingResult->total_credit ?? 0);

            if ($account->normal_balance->value === 'debit') {
                $beginningBalance = $beginningDebit - $beginningCredit;
                $endingBalance = $endingDebit - $endingCredit;
            } else {
                $beginningBalance = $beginningCredit - $beginningDebit;
                $endingBalance = $endingCredit - $endingDebit;
            }

            $netChange = $endingBalance - $beginningBalance;

            $type = $account->cashflow_type->value;
            switch ($type) {
                case 'operating':
                    $operating += $netChange;
                    break;
                case 'investing':
                    $investing += $netChange;
                    break;
                case 'financing':
                    $financing += $netChange;
                    break;
            }
        }

        $netCashflow = $operating + $investing + $financing;

        $pdf = DomPDF::loadView('reports.cashflow-pdf', [
            'operating' => $operating,
            'investing' => $investing,
            'financing' => $financing,
            'netCashflow' => $netCashflow,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);

        return $pdf->download('cashflow-' . $dateFrom . '-' . $dateTo . '.pdf');
    }

    // Excel Export Methods
    public function trialBalanceExcel(Request $request)
    {
        $asAtDate = $request->get('as_at_date', now()->toDateString());
        $accounts = Account::where('is_active', true)->get();

        $balances = JournalLine::query()
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.status', JournalEntryStatus::POSTED)
            ->where('journal_entries.entry_date', '<=', $asAtDate)
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type', 'accounts.normal_balance')
            ->selectRaw('accounts.id, accounts.code, accounts.name, accounts.type, accounts.normal_balance, SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
            ->get();

        $trialBalance = $accounts->map(function ($account) use ($balances) {
            $balance = $balances->firstWhere('id', $account->id);
            $debit = $balance->total_debit ?? 0;
            $credit = $balance->total_credit ?? 0;

            if ($account->normal_balance->value === 'debit') {
                $netBalance = $debit - $credit;
                $debitBalance = $netBalance > 0 ? $netBalance : 0;
                $creditBalance = $netBalance < 0 ? abs($netBalance) : 0;
            } else {
                $netBalance = $credit - $debit;
                $debitBalance = $netBalance < 0 ? abs($netBalance) : 0;
                $creditBalance = $netBalance > 0 ? $netBalance : 0;
            }

            return [
                'Account Code' => $account->code,
                'Account Name' => $account->name,
                'Type' => ucfirst($account->type->value),
                'Debit' => number_format($debitBalance, 2),
                'Credit' => number_format($creditBalance, 2),
            ];
        })->filter(function ($item) {
            return (float) str_replace(',', '', $item['Debit']) > 0 || (float) str_replace(',', '', $item['Credit']) > 0;
        })->sortBy('Account Code')
          ->values()
          ->toArray();

        $totalDebit = collect($trialBalance)->sum(function ($item) {
            return (float) str_replace(',', '', $item['Debit']);
        });
        $totalCredit = collect($trialBalance)->sum(function ($item) {
            return (float) str_replace(',', '', $item['Credit']);
        });

        $trialBalance[] = [
            'Account Code' => '',
            'Account Name' => 'TOTAL',
            'Type' => '',
            'Debit' => number_format($totalDebit, 2),
            'Credit' => number_format($totalCredit, 2),
        ];

        return Excel::download(new \App\Exports\TrialBalanceExport($trialBalance, $asAtDate), 'trial-balance-' . $asAtDate . '.xlsx');
    }

    public function ledgerExcel(Request $request)
    {
        $accountId = $request->get('account_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$accountId || !$dateFrom || !$dateTo) {
            return redirect()->back()->withErrors(['error' => 'Account, date from, and date to are required']);
        }

        $account = Account::findOrFail($accountId);
        $query = JournalLine::query()
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', JournalEntryStatus::POSTED)
            ->where('journal_lines.account_id', $accountId)
            ->whereBetween('journal_entries.entry_date', [$dateFrom, $dateTo])
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_lines.line_no')
            ->select([
                'journal_entries.id as entry_id',
                'journal_entries.entry_date',
                'journal_entries.reference',
                'journal_entries.description',
                'journal_lines.narration',
                'journal_lines.debit',
                'journal_lines.credit',
            ]);

        $beforeDateTransactions = JournalLine::query()
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', JournalEntryStatus::POSTED)
            ->where('journal_lines.account_id', $accountId)
            ->where('journal_entries.entry_date', '<', $dateFrom)
            ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
            ->first();

        $openingBalance = 0;
        if ($beforeDateTransactions) {
            if ($account->normal_balance->value === 'debit') {
                $openingBalance = ($beforeDateTransactions->total_debit ?? 0) - ($beforeDateTransactions->total_credit ?? 0);
            } else {
                $openingBalance = ($beforeDateTransactions->total_credit ?? 0) - ($beforeDateTransactions->total_debit ?? 0);
            }
        }

        $runningBalance = $openingBalance;
        $transactions = $query->get()->map(function ($transaction) use ($account, &$runningBalance) {
            if ($account->normal_balance->value === 'debit') {
                $runningBalance += $transaction->debit - $transaction->credit;
            } else {
                $runningBalance += $transaction->credit - $transaction->debit;
            }

            return [
                'Date' => $transaction->entry_date,
                'Reference' => $transaction->reference ?? '',
                'Description' => $transaction->description,
                'Narration' => $transaction->narration ?? '',
                'Debit' => number_format($transaction->debit, 2),
                'Credit' => number_format($transaction->credit, 2),
                'Balance' => number_format($runningBalance, 2),
            ];
        })->toArray();

        return Excel::download(new \App\Exports\LedgerExport($transactions, $account, $openingBalance, $runningBalance, $dateFrom, $dateTo), 'ledger-' . $account->code . '-' . $dateFrom . '-' . $dateTo . '.xlsx');
    }

    public function incomeStatementExcel(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        // Get individual revenue accounts
        $revenueAccounts = Account::where('type', AccountType::REVENUE)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $revenueItems = [];
        foreach ($revenueAccounts as $account) {
            $result = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->whereBetween('journal_entries.entry_date', [$dateFrom, $dateTo])
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $debit = (float) ($result->total_debit ?? 0);
            $credit = (float) ($result->total_credit ?? 0);
            $amount = $credit - $debit;

            if ($amount > 0) {
                $revenueItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }
        }

        // Get individual expense accounts
        $expenseAccounts = Account::where('type', AccountType::EXPENSE)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $expenseItems = [];
        foreach ($expenseAccounts as $account) {
            $result = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->whereBetween('journal_entries.entry_date', [$dateFrom, $dateTo])
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $debit = (float) ($result->total_debit ?? 0);
            $credit = (float) ($result->total_credit ?? 0);
            $amount = $debit - $credit;

            if ($amount > 0) {
                $expenseItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }
        }

        $totalRevenue = collect($revenueItems)->sum('amount');
        $totalExpenses = collect($expenseItems)->sum('amount');
        $netSurplus = $totalRevenue - $totalExpenses;

        $data = [];
        $data[] = ['REVENUE', '', ''];
        foreach ($revenueItems as $item) {
            $data[] = [$item['code'], $item['name'], number_format($item['amount'], 2)];
        }
        $data[] = ['', 'Total Revenue', number_format($totalRevenue, 2)];
        $data[] = ['', '', ''];
        $data[] = ['EXPENSES', '', ''];
        foreach ($expenseItems as $item) {
            $data[] = [$item['code'], $item['name'], number_format($item['amount'], 2)];
        }
        $data[] = ['', 'Total Expenses', number_format($totalExpenses, 2)];
        $data[] = ['', '', ''];
        $data[] = ['', 'Net Surplus', number_format($netSurplus, 2)];

        return Excel::download(new \App\Exports\IncomeStatementExport($data, $dateFrom, $dateTo), 'income-statement-' . $dateFrom . '-' . $dateTo . '.xlsx');
    }

    public function balanceSheetExcel(Request $request)
    {
        $asAtDate = $request->get('as_at_date', now()->toDateString());

        // Get individual asset accounts
        $assetAccounts = Account::where('type', AccountType::ASSET)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $assetItems = [];
        foreach ($assetAccounts as $account) {
            $balance = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $asAtDate)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $debit = (float) ($balance->total_debit ?? 0);
            $credit = (float) ($balance->total_credit ?? 0);

            if ($account->normal_balance->value === 'debit') {
                $amount = $debit - $credit;
            } else {
                $amount = $credit - $debit;
            }

            if ($amount > 0) {
                $assetItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }
        }

        // Get individual liability accounts
        $liabilityAccounts = Account::where('type', AccountType::LIABILITY)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $liabilityItems = [];
        foreach ($liabilityAccounts as $account) {
            $balance = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $asAtDate)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $debit = (float) ($balance->total_debit ?? 0);
            $credit = (float) ($balance->total_credit ?? 0);

            if ($account->normal_balance->value === 'credit') {
                $amount = $credit - $debit;
            } else {
                $amount = $debit - $credit;
            }

            if ($amount > 0) {
                $liabilityItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }
        }

        // Get individual equity accounts
        $equityAccountRecords = Account::where('type', AccountType::EQUITY)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $equityItems = [];
        foreach ($equityAccountRecords as $account) {
            $balance = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $asAtDate)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $debit = (float) ($balance->total_debit ?? 0);
            $credit = (float) ($balance->total_credit ?? 0);

            if ($account->normal_balance->value === 'credit') {
                $amount = $credit - $debit;
            } else {
                $amount = $debit - $credit;
            }

            if ($amount > 0) {
                $equityItems[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $amount,
                ];
            }
        }

        // Calculate current surplus
        $dateFrom = now()->startOfYear()->toDateString();
        $revenue = $this->getAccountTypeTotal(AccountType::REVENUE, $dateFrom, $asAtDate);
        $expenses = $this->getAccountTypeTotal(AccountType::EXPENSE, $dateFrom, $asAtDate);
        $currentSurplus = $revenue - $expenses;

        // Calculate totals
        $totalAssets = collect($assetItems)->sum('amount');
        $totalLiabilities = collect($liabilityItems)->sum('amount');
        $totalEquityAccounts = collect($equityItems)->sum('amount');
        $totalEquity = $totalEquityAccounts + $currentSurplus;
        $totalLiabilitiesEquity = $totalLiabilities + $totalEquity;

        $data = [];
        $data[] = ['Account Code', 'Account Name', 'Amount'];
        $data[] = ['ASSETS', '', ''];
        foreach ($assetItems as $item) {
            $data[] = [$item['code'], $item['name'], number_format($item['amount'], 2)];
        }
        $data[] = ['', 'Total Assets', number_format($totalAssets, 2)];
        $data[] = ['', '', ''];
        $data[] = ['LIABILITIES', '', ''];
        foreach ($liabilityItems as $item) {
            $data[] = [$item['code'], $item['name'], number_format($item['amount'], 2)];
        }
        $data[] = ['', 'Total Liabilities', number_format($totalLiabilities, 2)];
        $data[] = ['', '', ''];
        $data[] = ['EQUITY', '', ''];
        foreach ($equityItems as $item) {
            $data[] = [$item['code'], $item['name'], number_format($item['amount'], 2)];
        }
        if ($currentSurplus != 0) {
            $data[] = ['', 'Current Surplus', number_format($currentSurplus, 2)];
        }
        $data[] = ['', 'Total Equity', number_format($totalEquity, 2)];
        $data[] = ['', '', ''];
        $data[] = ['', 'Total Liabilities & Equity', number_format($totalLiabilitiesEquity, 2)];

        return Excel::download(new \App\Exports\BalanceSheetExport($data, $asAtDate), 'balance-sheet-' . $asAtDate . '.xlsx');
    }

    public function cashflowExcel(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        $beginningDate = date('Y-m-d', strtotime($dateFrom . ' -1 day'));
        $cashAccounts = Account::where('is_active', true)->where('is_cash', true)->get();

        $operating = 0;
        $investing = 0;
        $financing = 0;

        foreach ($cashAccounts as $account) {
            if (!$account->cashflow_type) {
                continue;
            }

            $beginningResult = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $beginningDate)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $beginningDebit = (float) ($beginningResult->total_debit ?? 0);
            $beginningCredit = (float) ($beginningResult->total_credit ?? 0);

            $endingResult = JournalLine::query()
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntryStatus::POSTED)
                ->where('journal_lines.account_id', $account->id)
                ->where('journal_entries.entry_date', '<=', $dateTo)
                ->selectRaw('SUM(journal_lines.debit) as total_debit, SUM(journal_lines.credit) as total_credit')
                ->first();

            $endingDebit = (float) ($endingResult->total_debit ?? 0);
            $endingCredit = (float) ($endingResult->total_credit ?? 0);

            if ($account->normal_balance->value === 'debit') {
                $beginningBalance = $beginningDebit - $beginningCredit;
                $endingBalance = $endingDebit - $endingCredit;
            } else {
                $beginningBalance = $beginningCredit - $beginningDebit;
                $endingBalance = $endingCredit - $endingDebit;
            }

            $netChange = $endingBalance - $beginningBalance;

            $type = $account->cashflow_type->value;
            switch ($type) {
                case 'operating':
                    $operating += $netChange;
                    break;
                case 'investing':
                    $investing += $netChange;
                    break;
                case 'financing':
                    $financing += $netChange;
                    break;
            }
        }

        $netCashflow = $operating + $investing + $financing;

        $data = [
            ['Cash from Operating Activities', number_format($operating, 2)],
            ['Cash from Investing Activities', number_format($investing, 2)],
            ['Cash from Financing Activities', number_format($financing, 2)],
            ['Net Cash Flow', number_format($netCashflow, 2)],
        ];

        return Excel::download(new \App\Exports\CashflowExport($data, $dateFrom, $dateTo), 'cashflow-' . $dateFrom . '-' . $dateTo . '.xlsx');
    }
}
