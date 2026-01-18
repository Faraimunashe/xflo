<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ledger - {{ $account->code }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h1 {
            text-align: center;
            margin-bottom: 5px;
        }
        h2 {
            text-align: center;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .account-info {
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .balance-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Paradise International School</h1>
        <h2>General Ledger</h2>
        <div class="account-info">
            <p><strong>Account:</strong> {{ $account->code }} - {{ $account->name }}</p>
            <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('F d, Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('F d, Y') }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Description</th>
                <th>Narration</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr class="balance-row">
                <td colspan="6" class="text-right">Opening Balance</td>
                <td class="text-right">{{ number_format($openingBalance, 2) }}</td>
            </tr>
            @foreach($transactions as $transaction)
            <tr>
                <td>{{ \Carbon\Carbon::parse($transaction['entry_date'])->format('Y-m-d') }}</td>
                <td>{{ $transaction['reference'] ?? '' }}</td>
                <td>{{ $transaction['description'] }}</td>
                <td>{{ $transaction['narration'] ?? '' }}</td>
                <td class="text-right">{{ number_format($transaction['debit'], 2) }}</td>
                <td class="text-right">{{ number_format($transaction['credit'], 2) }}</td>
                <td class="text-right">{{ number_format($transaction['balance'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="balance-row">
                <td colspan="6" class="text-right">Closing Balance</td>
                <td class="text-right">{{ number_format($closingBalance, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
