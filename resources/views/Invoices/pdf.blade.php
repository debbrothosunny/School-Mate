<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 14px;
            color: #333;
            margin: 0 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .header p {
            margin: 0;
            font-size: 16px;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h2 {
            border-bottom: 2px solid #555;
            padding-bottom: 5px;
            margin-bottom: 15px;
            font-size: 18px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table, th, td {
            border: 1px solid #999;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #eee;
        }
        .right-align {
            text-align: right;
        }
        .total-row td {
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Invoice #{{ $invoice->invoice_number }}</h1>
        <p>Date: {{ optional($invoice->created_at)->format('d-m-Y') ?? 'N/A' }}</p>
    </div>

    <div class="section">
        <h2>Student Information</h2>
        <p><strong>Name:</strong> {{ $invoice->student->name ?? 'N/A' }}</p>
        <p><strong>Class:</strong> {{ $invoice->student->class_name ?? 'N/A' }}</p>
        <p><strong>Session:</strong> {{ $invoice->student->session_name ?? 'N/A' }}</p>
    </div>

    <div class="section">
        <h2>Invoice Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="right-align">Amount (BDT)</th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($invoice->invoiceItems) && count($invoice->invoiceItems) > 0)
                    @foreach($invoice->invoiceItems as $item)
                        <tr>
                            <td>{{ $item->description ?? 'N/A' }}</td>
                            <td class="right-align">{{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="2">No invoice items found.</td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>Total Amount Due</td>
                    <td class="right-align">{{ number_format($invoice->total_amount_due, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Amount Paid</td>
                    <td class="right-align">{{ number_format($invoice->amount_paid, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Balance Due</td>
                    <td class="right-align">{{ number_format($invoice->balance_due, 2) }}</td>
                </tr>
                <tr>
                    <td>Due Date</td>
                    <td class="right-align">{{ optional($invoice->due_date)->format('d-m-Y') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td class="right-align">{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="footer">
        <p>Thank you for your payment. If you have any questions, please contact our office.</p>
        <p>Generated on {{ date('d-m-Y H:i') }}</p>
    </div>
</body>
</html>
