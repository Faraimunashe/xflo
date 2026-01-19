<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance Sheet - {{ $asAtDate }}</title>
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
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Paradise International School</h1>
        <h2>Balance Sheet</h2>
        <p>As at {{ \Carbon\Carbon::parse($asAtDate)->format('F d, Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-left">Account Code</th>
                <th class="text-left">Account Name</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section-header">
                <td colspan="3">ASSETS</td>
            </tr>
            @foreach($assetItems as $item)
            <tr>
                <td>{{ $item['code'] }}</td>
                <td>{{ $item['name'] }}</td>
                <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">Total Assets</td>
                <td class="text-right">{{ number_format($totalAssets, 2) }}</td>
            </tr>
            <tr><td colspan="3"></td></tr>
            <tr class="section-header">
                <td colspan="3">LIABILITIES</td>
            </tr>
            @foreach($liabilityItems as $item)
            <tr>
                <td>{{ $item['code'] }}</td>
                <td>{{ $item['name'] }}</td>
                <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">Total Liabilities</td>
                <td class="text-right">{{ number_format($totalLiabilities, 2) }}</td>
            </tr>
            <tr><td colspan="3"></td></tr>
            <tr class="section-header">
                <td colspan="3">EQUITY</td>
            </tr>
            @foreach($equityItems as $item)
            <tr>
                <td>{{ $item['code'] }}</td>
                <td>{{ $item['name'] }}</td>
                <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
            </tr>
            @endforeach
            @if($currentSurplus != 0)
            <tr>
                <td></td>
                <td>Current Surplus</td>
                <td class="text-right">{{ number_format($currentSurplus, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="2">Total Equity</td>
                <td class="text-right">{{ number_format($totalEquity, 2) }}</td>
            </tr>
            <tr><td colspan="3"></td></tr>
            <tr class="total-row">
                <td colspan="2">Total Liabilities & Equity</td>
                <td class="text-right">{{ number_format($totalLiabilitiesEquity, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
