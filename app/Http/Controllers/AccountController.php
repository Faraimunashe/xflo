<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Enums\AccountType;
use App\Enums\CashflowType;
use App\Enums\NormalBalance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Account::query()->with(['createdBy', 'updatedBy']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $accounts = $query->orderBy('code')->paginate(20);

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
            'filters' => $request->only(['search', 'type', 'is_active']),
            'accountTypes' => array_map(fn($case) => ['value' => $case->value, 'label' => ucfirst($case->value)], AccountType::cases()),
            'normalBalances' => array_map(fn($case) => ['value' => $case->value, 'label' => ucfirst($case->value)], NormalBalance::cases()),
            'cashflowTypes' => array_map(fn($case) => ['value' => $case->value, 'label' => ucfirst($case->value)], CashflowType::cases()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Accounts/Create', [
            'accountTypes' => array_map(fn($case) => ['value' => $case->value, 'label' => ucfirst($case->value)], AccountType::cases()),
            'normalBalances' => array_map(fn($case) => ['value' => $case->value, 'label' => ucfirst($case->value)], NormalBalance::cases()),
            'cashflowTypes' => array_map(fn($case) => ['value' => $case->value, 'label' => ucfirst($case->value)], CashflowType::cases()),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:accounts,code',
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'normal_balance' => 'required|string',
            'is_cash' => 'boolean',
            'cashflow_type' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        Account::create($validated);

        return redirect()->route('accounts.index')->with('success', 'Account created successfully.');
    }

    public function show(Account $account): Response
    {
        $account->load(['createdBy', 'updatedBy']);
        return Inertia::render('Accounts/Show', [
            'account' => $account,
        ]);
    }

    public function edit(Account $account): Response
    {
        return Inertia::render('Accounts/Edit', [
            'account' => $account,
            'accountTypes' => array_map(fn($case) => ['value' => $case->value, 'label' => ucfirst($case->value)], AccountType::cases()),
            'normalBalances' => array_map(fn($case) => ['value' => $case->value, 'label' => ucfirst($case->value)], NormalBalance::cases()),
            'cashflowTypes' => array_map(fn($case) => ['value' => $case->value, 'label' => ucfirst($case->value)], CashflowType::cases()),
        ]);
    }

    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:accounts,code,' . $account->id,
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'normal_balance' => 'required|string',
            'is_cash' => 'boolean',
            'cashflow_type' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();

        $account->update($validated);

        return redirect()->route('accounts.index')->with('success', 'Account updated successfully.');
    }

    public function destroy(Account $account)
    {
        $hasJournalLines = $account->journalLines()->exists();

        if ($hasJournalLines) {
            return back()->with('error', 'Cannot delete account that has journal entries.');
        }

        $account->delete();

        return redirect()->route('accounts.index')->with('success', 'Account deleted successfully.');
    }
}
