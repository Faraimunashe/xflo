<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trial Balance - {{ $asAtDate }}</title>
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
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
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
        <h2>Trial Balance</h2>
        <p>As at {{ \Carbon\Carbon::parse($asAtDate)->format('F d, Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Account Code</th>
                <th>Account Name</th>
                <th>Type</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($trialBalance as $item)
            <tr>
                <td>{{ $item['code'] }}</td>
                <td>{{ $item['name'] }}</td>
                <td>{{ ucfirst($item['type']) }}</td>
                <td class="text-right">{{ number_format($item['debit'], 2) }}</td>
                <td class="text-right">{{ number_format($item['credit'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3">TOTAL</td>
                <td class="text-right">{{ number_format($totalDebit, 2) }}</td>
                <td class="text-right">{{ number_format($totalCredit, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
