<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('journal-entries.index');
    }
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout');

    Route::resource('accounts', AccountController::class);

    Route::resource('journal-entries', JournalEntryController::class);
    Route::post('journal-entries/{journal_entry}/post', [JournalEntryController::class, 'post'])
        ->name('journal-entries.post');
    Route::post('journal-entries/{journal_entry}/reverse', [JournalEntryController::class, 'reverse'])
        ->name('journal-entries.reverse');

    Route::get('reports/ledger', [ReportController::class, 'ledger'])->name('reports.ledger');
    Route::get('reports/ledger/pdf', [ReportController::class, 'ledgerPdf'])->name('reports.ledger.pdf');
    Route::get('reports/ledger/excel', [ReportController::class, 'ledgerExcel'])->name('reports.ledger.excel');

    Route::get('reports/trial-balance', [ReportController::class, 'trialBalance'])->name('reports.trial-balance');
    Route::get('reports/trial-balance/pdf', [ReportController::class, 'trialBalancePdf'])->name('reports.trial-balance.pdf');
    Route::get('reports/trial-balance/excel', [ReportController::class, 'trialBalanceExcel'])->name('reports.trial-balance.excel');

    Route::get('reports/income-statement', [ReportController::class, 'incomeStatement'])->name('reports.income-statement');
    Route::get('reports/income-statement/pdf', [ReportController::class, 'incomeStatementPdf'])->name('reports.income-statement.pdf');
    Route::get('reports/income-statement/excel', [ReportController::class, 'incomeStatementExcel'])->name('reports.income-statement.excel');

    Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
    Route::get('reports/balance-sheet/pdf', [ReportController::class, 'balanceSheetPdf'])->name('reports.balance-sheet.pdf');
    Route::get('reports/balance-sheet/excel', [ReportController::class, 'balanceSheetExcel'])->name('reports.balance-sheet.excel');

    Route::get('reports/cashflow', [ReportController::class, 'cashflow'])->name('reports.cashflow');
    Route::get('reports/cashflow/pdf', [ReportController::class, 'cashflowPdf'])->name('reports.cashflow.pdf');
    Route::get('reports/cashflow/excel', [ReportController::class, 'cashflowExcel'])->name('reports.cashflow.excel');
});
