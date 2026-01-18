<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Income Statement - {{ $dateFrom }} to {{ $dateTo }}</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .section-header {
            font-weight: bold;
            background-color: #e9e9e9;
        }
        .section-header td {
            padding: 10px 8px;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Paradise International School</h1>
        <h2>Income Statement</h2>
        <p>For the period {{ \Carbon\Carbon::parse($dateFrom)->format('F d, Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('F d, Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Account Code</th>
                <th>Account Name</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section-header">
                <td colspan="3"><strong>REVENUE</strong></td>
            </tr>
            @foreach($revenueItems as $item)
            <tr>
                <td>{{ $item['code'] }}</td>
                <td>{{ $item['name'] }}</td>
                <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
            </tr>
            @endforeach
            @if(count($revenueItems) === 0)
            <tr>
                <td colspan="3" class="text-center text-gray-500">No revenue transactions</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="2"><strong>Total Revenue</strong></td>
                <td class="text-right"><strong>{{ number_format($totalRevenue, 2) }}</strong></td>
            </tr>
            <tr class="section-header">
                <td colspan="3"><strong>EXPENSES</strong></td>
            </tr>
            @foreach($expenseItems as $item)
            <tr>
                <td>{{ $item['code'] }}</td>
                <td>{{ $item['name'] }}</td>
                <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
            </tr>
            @endforeach
            @if(count($expenseItems) === 0)
            <tr>
                <td colspan="3" class="text-center text-gray-500">No expense transactions</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="2"><strong>Total Expenses</strong></td>
                <td class="text-right"><strong>{{ number_format($totalExpenses, 2) }}</strong></td>
            </tr>
            <tr class="total-row" style="border-top: 2px solid #333;">
                <td colspan="2"><strong>Net Surplus / (Deficit)</strong></td>
                <td class="text-right"><strong>{{ number_format($netSurplus, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
