<!DOCTYPE html>
<html>
<head>
    <title>Invoice</title>
    <style>
        /* A4 Optimization CSS */
        body { 
            font-family: 'Helvetica', sans-serif; 
            font-size: 11px; /* Reduced for space saving */
        }
        
        .invoice-box { 
            max-width: 800px; 
            margin: 10px auto; /* Reduced vertical margin */
            padding: 15px; /* Reduced padding */
            border: 1px solid #eee; 
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            position: relative; 
        }
        
        .invoice-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 10px; 
        }
        
        .school-info { text-align: center; }
        
        /* Reduce font size for school name */
        .school-info h1 {
            margin: 0;
            font-size: 16px;
        }
        .school-info p {
            margin: 3px 0 0 !important;
        }

        /* Titles and Headers */
        h2 { text-align: center; font-size: 18px; margin: 5px 0 5px 0; }
        h3 { margin-top: 10px; font-size: 14px; }
        
        .details-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
        }
        .details-table th, .details-table td { 
            border: 1px solid #ddd; 
            padding: 5px; /* Reduced padding */
            text-align: left; 
        }

        /* Signature and Images */
        .principal-signature { 
            text-align: right; 
            margin-top: 20px; /* Reduced vertical spacing */
        }
        .principal-signature p { margin: 0; }
        
        
        /* Dual Copy Styles */
        .copy-label {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 8px;
            font-weight: bold;
            padding: 3px 8px;
            border: 2px solid;
            border-radius: 5px;
        }

        .cut-line {
            max-width: 800px; 
            margin: 15px auto; 
            border-bottom: 2px dashed #666; 
            text-align: center;
            line-height: 0.1em;
            font-size: 10px;
            color: #666;
        }
        .cut-line span {
            background: #fff;
            padding: 0 10px;
        }
        
        .invoice-footer {
            margin-top: 15px; 
            padding-top: 10px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>

    <div class="invoice-box">
        <div class="copy-label" style="color: #d9534f; border-color: #d9534f;">OFFICE COPY</div>

        <div class="invoice-header">
            <div class="school-logo">
                @if($settings->school_logo)
                    <img src="{{ public_path('storage/' . $settings->school_logo) }}" alt="School Logo">
                @endif
            </div>

            <div class="school-info">
                @if($settings)
                    <h1>{{ $settings->school_name }}</h1>
                    <p>{{ $settings->address }}</p>
                    <p>Phone: {{ $settings->phone_number }}</p>
                @endif
            </div>
            
            <div style="text-align: right;">
                <strong>Invoice Number:</strong> {{ $invoice->invoice_number }}<br>
                <strong>Date:</strong> {{ $invoice->created_at->format('M d, Y') }}<br>
                <strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
            </div>
        </div>

        <h2>Payment Receipt</h2>
        
        <table class="details-table">
            <thead>
                <tr>
                    <th colspan="2">Student & Invoice Details</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Student Name:</strong> {{ $invoice->student->name }}</td>
                    <td><strong>Student ID:</strong> {{ $invoice->student->admission_number }}</td>
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



        <div class="principal-signature"> 
            <p style="text-align: left; margin-top: 15px; font-size: 10px;">
                Collected By: {{ $collectorName ?? 'N/A' }}
            </p>
        </div>

    </div>
    
    <div class="cut-line"><span>CUT ALONG THE DOTTED LINE</span></div>


    <div class="invoice-box">
        <div class="copy-label" style="color: #5cb85c; border-color: #5cb85c;">STUDENT COPY</div>

        <div class="invoice-header">
            <div class="school-logo">
                @if($settings->school_logo)
                    <img src="{{ public_path('storage/' . $settings->school_logo) }}" alt="School Logo">
                @endif
            </div>

            <div class="school-info">
                @if($settings)
                    <h1>{{ $settings->school_name }}</h1>
                    <p>{{ $settings->address }}</p>
                    <p>Phone: {{ $settings->phone_number }}</p>
                @endif
            </div>
            
            <div style="text-align: right;">
                <strong>Invoice Number:</strong> {{ $invoice->invoice_number }}<br>
                <strong>Date:</strong> {{ $invoice->created_at->format('M d, Y') }}<br>
                <strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
            </div>
        </div>

        <h2>Payment Receipt</h2>
        
        <table class="details-table">
            <thead>
                <tr>
                    <th colspan="2">Student & Invoice Details</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Student Name:</strong> {{ $invoice->student->name }}</td>
                    <td><strong>Student ID:</strong> {{ $invoice->student->admission_number }}</td>
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

        <div class="principal-signature"> 
             <p style="text-align: left; margin-top: 15px; font-size: 10px;">
                Collected By: {{ $collectorName ?? 'N/A' }}
            </p>
    </div>

    <div class="invoice-footer">
        &copy; {{ date('Y') }} All Rights Reserved. Designed & Developed by Smith iT.
    </div>
</body>
</html>