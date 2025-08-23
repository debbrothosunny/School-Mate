<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
            color: #333;
        }
        .container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 24px;
            color: #007BFF;
            margin: 0;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .info-left, .info-right {
            width: 48%;
        }
        .info-left p, .info-right p {
            margin: 5px 0;
        }
        .info-label {
            font-weight: bold;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .invoice-table th, .invoice-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .invoice-table th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .total-section {
            text-align: right;
        }
        .total-row {
            padding: 5px 0;
        }
        .total-label {
            font-weight: bold;
            margin-right: 10px;
        }
        .total-value {
            font-weight: bold;
            color: #007BFF;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            font-style: italic;
            color: #888;
        }
        /* Custom styles for dompdf rendering */
        @page {
            margin: 20px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>INVOICE</h1>
        </div>

        <div class="info-section">
            <div class="info-left">
                <p><span class="info-label">Invoice Number:</span> {{ $invoice->invoice_number }}</p>
                <p><span class="info-label">Issued On:</span> {{ \Carbon\Carbon::parse($invoice->issued_at)->format('F d, Y') }}</p>
                <p><span class="info-label">Due Date:</span> {{ \Carbon\Carbon::parse($invoice->due_date)->format('F d, Y') }}</p>
            </div>
            <div class="info-right">
                <p><span class="info-label">Student Name:</span> {{ optional($invoice->student)->name }}</p>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount (BDT)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->invoiceItems as $item)
                <tr>
                    <td>{{ optional($item->feeType)->name }}</td>
                    <td style="text-align: right;">{{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-section">
            <p class="total-row">
                <span class="total-label">Total Due:</span>
                <span class="total-value">BDT {{ number_format($invoice->total_amount_due, 2) }}</span>
            </p>
            <p class="total-row">
                <span class="total-label">Amount Paid:</span>
                <span class="total-value">BDT {{ number_format($invoice->amount_paid, 2) }}</span>
            </p>
            <p class="total-row">
                <span class="total-label">Balance Due:</span>
                <span class="total-value" style="color: #dc3545;">BDT {{ number_format($invoice->balance_due, 2) }}</span>
            </p>
        </div>

        <div class="footer">
            <p>Thank you for your timely payment.</p>
        </div>
    </div>

</body>
</html>
