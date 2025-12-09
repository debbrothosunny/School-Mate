<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Certificate Design</title>

    <style>
    @page {
        size: A4 landscape;
        margin: 0;
    }

    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
    }

    .certificate-container {
        width: 297mm;
        height: 210mm;
        background: white;
        padding: 15mm;
        box-sizing: border-box;
        position: relative;
        overflow: hidden;
        border: 10px solid #4f46e5;
        border-image: linear-gradient(45deg, #4f46e5, #9333ea) 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .decorative-border {
        position: absolute;
        top: 5px;
        left: 5px;
        right: 5px;
        bottom: 5px;
        z-index: 1;
    }

    .cert-header {
        text-align: center;
        margin-bottom: 5mm;
        padding-top: 3mm;
        position: relative;
        z-index: 10;
    }

    .cert-logo {
        height: 22mm;
        width: 22mm;
        margin: 0 auto 2mm;
        border-radius: 50%;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        display: block;
    }

    .cert-title {
        font-size: 18pt;
        font-weight: 800;
        color: #4f46e5;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin: 0;
    }

    .cert-contact {
        font-size: 10pt;
        color: #6b7280;
        margin: 1mm 0 1mm;
    }

    .cert-address {
        font-size: 10pt;
        color: #6b7280;
        margin: 0 0 2mm;
    }

    .cert-hr {
        border: 0;
        height: 2px;
        background: #a5b4fc;
        width: 70%;
        margin: 0 auto;
        border-radius: 1px;
    }

    .cert-main-title {
        text-align: center;
        margin-bottom: 5mm;
        position: relative;
        z-index: 10;
    }

    .cert-main-title h2 {
        font-size: 14pt;
        font-weight: 700;
        color: #1f2937;
        border-bottom: 3px dashed #9ca3af;
        display: inline-block;
        padding: 1mm 15mm;
        background-color: #fffbeb;
        border-radius: 5px;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .cert-main {
        font-size: 10pt;
        color: #1f2937;
        line-height: 1.5;
        position: relative;
        z-index: 10;
        padding: 0;
        flex-grow: 1;
        margin-bottom: 3mm;
    }

    .cert-table {
        width: calc(100% - 30mm);
        margin: 2mm 15mm;
        border-collapse: collapse;
    }

    .cert-table th,
    .cert-table td {
        padding: 1.2mm 0;
        border-bottom: 1px solid #e5e7eb;
        font-size: 9pt;
    }

    .cert-table th {
        text-align: left;
        font-weight: 600;
        color: #4b5563;
        width: 30%;
    }

    .cert-table td {
        font-weight: 500;
        color: #1f2937;
    }

    .cert-note {
        width: calc(100% - 30mm);
        margin: 4mm 15mm 0 15mm;
        font-style: italic;
        color: #4b5563;
        font-size: 8pt;
    }

    .cert-footer {
        padding-top: 5mm;
        position: relative;
        z-index: 10;
        padding-left: 15mm;
        padding-right: 15mm;
    }

    .cert-signatures {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }

    .cert-signatures .left-block {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .cert-signatures .right-block {
        display: flex;
        margin-right: 60px;
        flex-direction: column;
        align-items: flex-end;
        text-align: right;
    }

    .cert-seal {
        height: 12mm;
        width: 12mm;
        margin: 2mm 0 1mm 0;
        border: 2px solid #ef4444;
        border-radius: 50%;
        line-height: 12mm;
        font-size: 8pt;
        color: #b91c1c;
        background-color: #fef2f2;
        opacity: 0.8;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .principal-line {
        border-top: 1px solid #1f2937;
        width: 45mm;
        margin-top: 2mm;
        margin-bottom: 1mm;
    }

    .cert-watermark {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        opacity: 0.05;
    }

    .cert-watermark span {
        font-size: 80pt;
        font-weight: 800;
        color: #c7d2fe;
        transform: rotate(-45deg);
        white-space: nowrap;
    }
    </style>
</head>

<body>

<div class="certificate-container">

    <!-- Watermark -->
    <div class="cert-watermark">CERTIFICATE</div>

    <!-- Header -->
    <header class="cert-header">
        @if ($logoBase64)
            <img src="{{ $logoBase64 }}" alt="School Logo" class="cert-logo" />
        @else
            <img src="https://placehold.co/85x85/1e40af/ffffff?text=LOGO" alt="School Logo" class="cert-logo" />
        @endif

        <h1 class="cert-title">{{ $setting->school_name ?? 'ABCD School' }}</h1>
        <p class="cert-contact">Mobile: {{ $setting->phone_number ?? '+880 123456789' }}</p>
        <p class="cert-address">{{ $setting->address ?? 'Not Provided' }}</p>
        <hr class="cert-hr" />
    </header>

    <!-- Title -->
    <div class="cert-main-title">
        <h2>TRANSFER CERTIFICATE</h2>
    </div>

    <!-- Main Content -->
    <main class="cert-main">
        <p style="text-align: justify; margin: 0 15mm 15mm;">
            This is to certify that <strong style="color: #1e40af; font-size: 13pt;">{{ $student->name ?? 'Md. ABCD' }}</strong>,<br>
            son/daughter of <strong>{{ $student->parent_name ?? 'Mr.ABCD' }}</strong>,<br>
            bearing <strong>Admission No:</strong> <span style="color: #1e40af;">{{ $student->admission_number ?? '0123456' }}</span> and
            <strong>Roll No:</strong> <span style="color: #1e40af;">{{ $student->roll_number ?? '123456' }}</span> was a regular student of this institution.
        </p>

        <table class="cert-table">
            <tbody>
                <tr>
                    <th>Guardian Name</th>
                    <td>{{ $student->parent_name ?? 'Mr. Not Provide' }}</td>
                </tr>
                <tr>
                    <th>Date of Birth</th>
                    <td>{{ \Carbon\Carbon::parse($student->date_of_birth ?? 'Not Provided')->format('jS F, Y') }} ({{ \Carbon\Carbon::parse($student->date_of_birth ?? 'Not Provided')->age }} years old)</td>
                </tr>
                <tr>
                    <th>Class Last Studied</th>
                    <td>Class {{ $student->className->class_name ?? 'Not Provided' }}, Session: {{ $sessionName ?? 'Not Provided' }}</td>
                </tr>
                <tr>
                    <th>Conduct & Character</th>
                    <td>{{ $conduct ?? 'Very Good' }}</td>
                </tr>
                <tr>
                    <th>Reason for Leaving</th>
                    <td>{{ $reason ?? "Guardian's request for transfer to another institution" }}</td>
                </tr>
                <tr>
                    <th>Date of Leaving</th>
                    <td>{{ now()->format('jS F, Y') }}</td>
                </tr>
            </tbody>
        </table>

        <p class="cert-note">
            During his/her stay at the school, the student was found sincere, well-behaved and regular in studies. 
            We wish him/her every success in life.
        </p>
    </main>

    <!-- Footer & Signatures -->
    <footer class="cert-footer">
        <div class="cert-signatures">
            <div class="left-block">
                <p style="font-weight: 600;">Date of Issue: {{ $date }}</p>
                <div class="cert-seal">
                    <span>SEAL</span>
                </div>
            </div>

            <div class="right-block">
                <p style="font-weight: 700; font-size: 12pt;">
                    {{ $setting->principal_name ?? 'Not Provided' }}
                </p>
                <div class="principal-line"></div>
                <p style="font-weight: 700; margin: 5px 0;">Head Master</p>
                <p style="font-size: 10pt; color: #6b7280;">
                    {{ $setting->school_name ?? 'ABCD School' }}
                </p>
            </div>
        </div>
    </footer>
</div>

</body>

</html>