<!DOCTYPE html>
<html>
<head>
    <title>Teacher ID Card (Front & Back - Portrait)</title>
    <style>
        /* CRITICAL: Set @page margin to a small value for maximum use of space */
        @page { 
            margin: 10mm; /* Minimal margin for A4 */
            size: A4 portrait; 
        }

        /* --- VARIABLES --- */
        :root {
            --primary-blue: #000080; 
            --secondary-blue: #0A327A; 
            --card-bg: #fff;
            --text-dark: #1a1a1a;
            --text-light: #fff;
            --border-radius: 3mm; 
        }

        /* --- BASE STYLES --- */
        body {
            background: #f0f0f0;
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            page-break-after: always; /* Ensure a break after each teacher's set of cards */
        }
        
        /* FIX 1: The container for the two cards */
        /* Total width required: 54mm (Front) + 54mm (Back) + 10mm (Gap) = 118mm */
        .print-sheet {
            width: 118mm; /* Fixed width to hold both cards + gap */
            margin: 15mm auto 0 auto; /* Centers the card pair horizontally on the A4 page */
            padding-bottom: 5mm;
        }

        /* CRITICAL FIX: Standard Portrait ID Card Size (ISO/IEC 7810 ID-1) */
        .id-card {
            width: 54mm;
            height: 85.6mm;
            border-radius: var(--border-radius);
            box-shadow: 0 0.5mm 1.5mm rgba(0, 0, 0, 0.2);
            background: var(--card-bg);
            overflow: hidden;
            position: relative;
            border: 0.1mm solid #e0e0e0;
            
            /* FIX 2: Float the cards and add margin for separation */
            float: left;
            margin-right: 10mm; /* Adds 10mm gap between the two cards */
        }
        .id-card:last-child {
            margin-right: 0; /* No margin on the last card */
        }

        /* --- Clearfix to ensure .print-sheet wraps the floats --- */
        .print-sheet::after {
            content: "";
            display: table;
            clear: both;
        }
        /* ----------------------------------------------------------------- */

        /* ----------- FRONT SIDE CONTENT LAYOUT (FROM YOUR FIRST CODE) ------------ */

        .id-front .header {
            padding: 0;
            height: 38mm;
            position: relative;
            z-index: 10;
        }
        
        .id-front .header-background-shape {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--primary-blue);
            background-size: cover;
            background-position: center bottom;
            background-repeat: no-repeat;
            z-index: 1;
        }

        .id-front .header-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            text-align: center;
            color: var(--text-light);
            z-index: 11;
        }

        .id-front .header-content h2 {
            margin-top: 20mm;
            font-size: 4mm;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.5mm;
        }
        
        .id-front .header .school-logo {
            position: absolute;
            top: 3mm;
            left: 50%;
            transform: translateX(-50%);
            width: 15mm;
            height: 15mm;
            border-radius: 50%;
            object-fit: cover;
            z-index: 12;
        }

        /* Teacher Photo Position/Style (Overlaps header) */
        .id-front .photo-box {
            width: 18mm;
            height: 18mm;
            border: 1mm solid var(--card-bg);
            border-radius: 50%;
            margin: -8mm auto 4mm auto;
            overflow: hidden;
            z-index: 12;
            box-shadow: 0 1mm 3mm rgba(0, 0, 0, 0.25);
            position: relative;
        }
        
        .id-front .photo-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6mm;
            color: var(--text-light);
            background-color: var(--primary-blue);
            border-radius: 50%;
            font-weight: 700;
        }

        /* Details Section - ALIGNMENT FIX */
        .id-front .details {
            padding: 0 4mm;
            margin-top: 1mm; 
        }

        .id-front .details p {
            display: flex;
            align-items: center;
            margin: 1.1mm 0; 
            font-size: 1.8mm;
            color: var(--text-dark);
        }

        .id-front .details p strong {
            display: inline-block;
            width: 20mm; 
            text-align: center;
            font-weight: 500;
            color: #333;
            margin-right: 1mm;
        }

        .id-front .details p span {
            font-weight: 700;
            color: #000;
            padding-left: 2mm; 
        }
        
        .id-front .details p strong::after {
            content: "";
            padding-left: 1mm;
            font-weight: 500;
        }

        /* Footer Bar with address */
        .id-front .card-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: var(--secondary-blue);
            color: var(--text-light);
            text-align: center;
            font-size: 1.5mm;
            font-weight: 700;
            z-index: 10;
        }


        /* ------------ BACK SIDE CONTENT LAYOUT (FROM YOUR FIRST CODE) ------------ */
        .id-back {
            text-align: center;
        }

        .id-back .header {
            color: var(--text-dark);
            padding: 5mm 5mm 0 5mm; 
            height: 15mm;
            position: relative;
            z-index: 10;
        }

        .id-back .header h2 {
            color: var(--secondary-blue);
            font-size: 3.5mm;
            margin-bottom: 2mm;
        }

        .id-back .section {
            padding: 0 6mm;
            margin-top: 4mm; 
            text-align: left;
        }

        .id-back .section-title {
            color: var(--text-dark);
            border-bottom: 0.5mm solid var(--secondary-blue);
            padding-bottom: 0.5mm;
            display: block;
            width: fit-content;
            margin: 0 auto 2mm auto;
            font-weight: 700;
            font-size: 2.5mm;
            text-align: center
        }

        .id-back .section ul {
            list-style-type: disc;
            margin: 2mm 0 0 3mm;
            padding-left: 3mm;
            font-size: 2mm;
            line-height: 1.4;
            color: #333;
        }
        
        /* Signature Area */
        .id-back .signature-area {
            padding: 0 6mm;
            text-align: center;
            position: absolute; 
            bottom: 5mm; 
            left: 0;
            width: 100%;
        }

        .id-back .signature-area h4 {
            color: var(--text-dark);
            font-weight: 500;
            margin-bottom: 1mm;
            font-size: 2mm;
        }

        .id-back .footer-sign {
            color: var(--secondary-blue); 
            font-weight: bold;
            border-top: 0.5mm solid var(--text-dark);
            display: inline-block;
            padding-top: 0.5mm;
            font-size: 2.5mm;
        }
        
        /* Bottom Bar (Re-implemented for Back Side) */
        .id-back .bottom-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2mm;
            background: var(--secondary-blue);
            display: block;
        }
    </style>
</head>

<body>
    @php
        // Global variables (only logo and shape – teacher photo is per-teacher)
        $logoSrc       = $setting->logo_base64 ?? 'https://placehold.co/80x80/008080/FFFFFF?text=LOGO';
        $shapeBase64Url = $shapeBase64Url ?? 'background-image: none;';
    @endphp

    @foreach($teachers as $teacher)
        @php
            // Get current teacher's photo from the array passed by controller
            $currentTeacherPhotoSrc = $teacherPhotos[$teacher->id] 
                ?? 'https://placehold.co/140x140/008080/FFFFFF?text=' . substr($teacher->name ?? 'T', 0, 1);

            // Check if it's a real base64 image (not placeholder URL)
            $isRealImage = str_starts_with($currentTeacherPhotoSrc, 'data:') 
                && strlen($currentTeacherPhotoSrc) > 100;
        @endphp

        <div class="print-sheet">
            {{-- FRONT CARD --}}
            <div class="id-card id-front">

                <div class="header">
                    {{-- 1. Background Shape Layer --}}
                    <div class="header-background-shape" style="{!! $shapeBase64Url !!}"></div>

                    {{-- 2. Header Content --}}
                    <div class="header-content">
                        <img src="{{ $logoSrc }}" alt="Logo" class="school-logo">
                        <h2>{{ $setting->school_name ?? 'ABCD SCHOOL' }}</h2>
                        <div style="font-size: 2mm; font-weight: 500;">Teacher ID CARD</div>
                    </div>
                </div>

                {{-- TEACHER PHOTO --}}
                <div class="photo-box">
                    @if($isRealImage)
                        <img src="{{ $currentTeacherPhotoSrc }}" 
                             style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit;" 
                             alt="Teacher Photo">
                    @else
                        <div class="photo-placeholder" 
                             style="background-color: var(--primary-blue); color: white; display: flex; align-items: center; justify-content: center; font-size: 36px; font-weight: bold;">
                            {{ strtoupper(substr($teacher->name ?? 'T', 0, 1)) }}
                        </div>
                    @endif
                </div>

                <div class="details" style="padding: 1px 15px; font-size: 8px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 38%; padding: 3px 0; font-weight: bold;">Employee ID</td>
                            <td style="width: 42%; padding: 3px 0;">{{ $teacher->joining_number ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Designation</td>
                            <td>{{ $teacher->designation ?? 'Teacher' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Name</td>
                            <td>{{ $teacher->name ?? 'Unknown' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Joining Date</td>
                            <td>{{ $teacher->joining_date ? \Carbon\Carbon::parse($teacher->joining_date)->format('d M, Y') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Emergency-Call</td>
                            <td>{{ $teacher->emergency_contact ?? $setting->phone_number ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>

                {{-- FOOTER --}}
                <div class="card-footer">
                    {{ $setting->address ?? 'Not Provide, Sylhet' }}
                    <p>Design and Developed by Smith IT</p>
                </div>
            </div>

            {{-- BACK CARD --}}
            <div class="id-card id-back">
                <div class="header">
                    <h2>{{ $setting->school_name ?? 'ABCD SCHOOL' }}</h2>
                </div>
                
                <div class="section">
                    <div class="section-title">TERMS AND CONDITIONS</div>
                    <ul>
                        <li>This card must be presented upon request within campus.</li>
                        <li>Report loss of card to the office immediately.</li>
                        <li>Misuse will result in disciplinary measures.</li>
                        <li>This card is non-transferable and property of the school.</li>
                    </ul>
                </div>
                
                <div class="signature-area">
                    <h4>PRINCIPAL SIGNATURE</h4>
                    <div class="footer-sign">{{ $setting->principal_name ?? 'Not Available' }}</div>
                </div>
                
                <div class="bottom-bar"></div>
            </div>
        </div>
    @endforeach
</body>
</html> 