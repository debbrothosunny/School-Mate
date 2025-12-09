<!DOCTYPE html>
<html>
<head>
    <title>Invoice</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); }
        .invoice-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .school-info { text-align: center; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .details-table th, .details-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .principal-signature { text-align: right; margin-top: 50px; }
        .principal-signature p { margin: 0; }
        .school-logo, .principal-signature img { max-width: 150px; height: auto; }
        
        /* The following styles are crucial for Dompdf to handle images properly */
        .school-logo img, .principal-signature img {
            max-height: 80px;
            /* Dompdf needs absolute path for images */
            content: url('{{ $settings->school_logo ? public_path("storage/{$settings->school_logo}") : '' }}');
        }
        .principal-signature img {
            content: url('{{ $settings->principal_signature ? public_path("storage/{$settings->principal_signature}") : '' }}');
        }
        
        /* NEW: Footer style */
        .invoice-footer {
            margin-top: 50px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 10px; /* Smaller font for copyright info */
            color: #666;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="invoice-header">
            <div class="school-logo">
                @if($settings->school_logo)
                    <img src="{{ public_path('storage/' . $settings->school_logo) }}" alt="School Logo">
                @endif
            </div>

            <div class="school-info">
                @if($settings)
                    <h1 style="margin: 0;">{{ $settings->school_name }}</h1>
                    <p style="margin: 5px 0 0;">{{ $settings->address }}</p>
                    <p style="margin: 5px 0 0;">Phone: {{ $settings->phone_number }} 
                @endif
            </div>
            
            <div style="text-align: right;">
                <strong>Invoice Number:</strong> {{ $invoice->invoice_number }}<br>
                <strong>Date:</strong> {{ $invoice->created_at->format('M d, Y') }}<br>
                <strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
            </div>
        </div>

        <h2 style="text-align: center;">Payment Receiptwwww</h2>
        
        <table class="details-table">
            <thead>
                <tr>
                    <th colspan="2">Student & Invoice Details</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Student Name:</strong> {{ $invoice->student->name }}</td>
                    <td><strong>Admission No:</strong> {{ $invoice->student->admission_number }}</td>
                </tr>
                <tr>
                    <td><strong>Class:</strong> {{ optional($invoice->student->className)->class_name ?? 'N/A' }}</td>
                    <td><strong>Invoice Amount:</strong> TK {{ number_format($invoice->total_amount_due, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Amount Paid:</strong> TK {{ number_format($invoice->amount_paid, 2) }}</td>
                    <td><strong>Balance Due:</strong> TK {{ number_format($invoice->balance_due, 2) }}</td>
                </tr>
            </tbody>
        </table>

        @if($invoice->payments->count() > 0)
        <h3 style="margin-top: 20px;">Payment History</h3>
        <table class="details-table">
            <thead>
                <tr>
                    <th>Payment Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->payments as $payment)
                    <tr>
                        <td>{{ $payment->created_at->format('M d, Y') }}</td>
                        <td>TK {{ number_format($payment->amount, 2) }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="principal-signature"> 
             <p>___________________________</p>
            @if($settings->principal_signature)
                <img src="{{ public_path('storage/' . $settings->principal_signature) }}" alt="Principal Signature">
            @endif
            <p>Principal: {{ $settings->principal_name }}</p>
        </div>
    </div>
    
    <div class="invoice-footer">
        &copy; {{ date('Y') }} All Rights Reserved. Designed & Developed by **Smith iT**.
    </div>
</body>
</html>