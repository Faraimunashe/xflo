<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cash Flow Statement - {{ $dateFrom }} to {{ $dateTo }}</title>
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
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .positive {
            color: #22c55e;
        }
        .negative {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Paradise International School</h1>
        <h2>Cash Flow Statement</h2>
        <p>For the period {{ \Carbon\Carbon::parse($dateFrom)->format('F d, Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('F d, Y') }}</p>
    </div>

    <table>
        <tbody>
            <tr>
                <td>Cash from Operating Activities</td>
                <td class="text-right {{ $operating >= 0 ? 'positive' : 'negative' }}">{{ number_format($operating, 2) }}</td>
            </tr>
            <tr>
                <td>Cash from Investing Activities</td>
                <td class="text-right {{ $investing >= 0 ? 'positive' : 'negative' }}">{{ number_format($investing, 2) }}</td>
            </tr>
            <tr>
                <td>Cash from Financing Activities</td>
                <td class="text-right {{ $financing >= 0 ? 'positive' : 'negative' }}">{{ number_format($financing, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Net Cash Flow</td>
                <td class="text-right {{ $netCashflow >= 0 ? 'positive' : 'negative' }}">{{ number_format($netCashflow, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
