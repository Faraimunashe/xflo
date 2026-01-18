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
        <tbody>
            <tr class="section-header">
                <td>ASSETS</td>
                <td></td>
            </tr>
            <tr>
                <td>Total Assets</td>
                <td class="text-right">{{ number_format($assets, 2) }}</td>
            </tr>
            <tr><td colspan="2"></td></tr>
            <tr class="section-header">
                <td>LIABILITIES</td>
                <td></td>
            </tr>
            <tr>
                <td>Total Liabilities</td>
                <td class="text-right">{{ number_format($liabilities, 2) }}</td>
            </tr>
            <tr><td colspan="2"></td></tr>
            <tr class="section-header">
                <td>EQUITY</td>
                <td></td>
            </tr>
            <tr>
                <td>Equity Accounts</td>
                <td class="text-right">{{ number_format($equity - $currentSurplus, 2) }}</td>
            </tr>
            <tr>
                <td>Current Surplus</td>
                <td class="text-right">{{ number_format($currentSurplus, 2) }}</td>
            </tr>
            <tr>
                <td>Total Equity</td>
                <td class="text-right">{{ number_format($equity, 2) }}</td>
            </tr>
            <tr><td colspan="2"></td></tr>
            <tr class="total-row">
                <td>Total Liabilities & Equity</td>
                <td class="text-right">{{ number_format($totalLiabilitiesEquity, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
