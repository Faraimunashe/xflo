<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\CashflowType;
use App\Enums\NormalBalance;
use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'code' => '1001',
                'name' => 'Cash on Hand',
                'type' => AccountType::ASSET,
                'normal_balance' => NormalBalance::DEBIT,
                'is_cash' => true,
                'cashflow_type' => CashflowType::OPERATING,
                'is_active' => true,
            ],
            [
                'code' => '1002',
                'name' => 'Bank Account',
                'type' => AccountType::ASSET,
                'normal_balance' => NormalBalance::DEBIT,
                'is_cash' => true,
                'cashflow_type' => CashflowType::OPERATING,
                'is_active' => true,
            ],
            [
                'code' => '4001',
                'name' => 'Tuition Fees',
                'type' => AccountType::REVENUE,
                'normal_balance' => NormalBalance::CREDIT,
                'is_cash' => false,
                'cashflow_type' => null,
                'is_active' => true,
            ],
            [
                'code' => '4002',
                'name' => 'Levies Income',
                'type' => AccountType::REVENUE,
                'normal_balance' => NormalBalance::CREDIT,
                'is_cash' => false,
                'cashflow_type' => null,
                'is_active' => true,
            ],
            [
                'code' => '5001',
                'name' => 'Salaries & Wages',
                'type' => AccountType::EXPENSE,
                'normal_balance' => NormalBalance::DEBIT,
                'is_cash' => false,
                'cashflow_type' => null,
                'is_active' => true,
            ],
            [
                'code' => '5002',
                'name' => 'Utilities',
                'type' => AccountType::EXPENSE,
                'normal_balance' => NormalBalance::DEBIT,
                'is_cash' => false,
                'cashflow_type' => null,
                'is_active' => true,
            ],
            [
                'code' => '5003',
                'name' => 'Stationery',
                'type' => AccountType::EXPENSE,
                'normal_balance' => NormalBalance::DEBIT,
                'is_cash' => false,
                'cashflow_type' => null,
                'is_active' => true,
            ],
            [
                'code' => '5004',
                'name' => 'Fuel & Transport',
                'type' => AccountType::EXPENSE,
                'normal_balance' => NormalBalance::DEBIT,
                'is_cash' => false,
                'cashflow_type' => null,
                'is_active' => true,
            ],
            [
                'code' => '5009',
                'name' => 'Bank Charges',
                'type' => AccountType::EXPENSE,
                'normal_balance' => NormalBalance::DEBIT,
                'is_cash' => false,
                'cashflow_type' => null,
                'is_active' => true,
            ],
            [
                'code' => '3001',
                'name' => 'Retained Earnings',
                'type' => AccountType::EQUITY,
                'normal_balance' => NormalBalance::CREDIT,
                'is_cash' => false,
                'cashflow_type' => null,
                'is_active' => true,
            ],
        ];

        foreach ($accounts as $account) {
            Account::create($account);
        }
    }
}
