<!DOCTYPE html>
<html>

<head>
    <title>Teacher ID Card (Front & Back - Portrait)</title>
    <style>
        /* CRITICAL: Set @page margin to a small value or 0 for maximum use of space */
        @page { margin: 10mm; size: A4; }

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
        }
        
        /* --- CRITICAL DOMPDF FIX: Using float to ensure side-by-side layout --- */
        .card-wrapper {
            width: 100%;
            text-align: center; /* Used to center the floated items */
            padding-top: 15mm;
            /* Flexbox removed for Dompdf compatibility */
        }

        /* CRITICAL FIX: Standard Portrait ID Card Size (ISO/IEC 7810 ID-1) */
        .id-card {
            width: 54mm;
            height: 85.6mm;
            border-radius: var(--border-radius);
            box-shadow: 0 1mm 3mm rgba(0, 0, 0, 0.15);
            background: var(--card-bg);
            overflow: hidden;
            position: relative;
            border: 0.1mm solid #e0e0e0;
            
            /* --- DOMPDF FIX: Float the cards and add margin for spacing --- */
            float: left;
            margin: 0 5mm; /* Adds 5mm space on left and right of each card, totaling 10mm gap */
        }

        /* --- Clearfix to ensure .card-wrapper wraps the floats --- */
        .card-wrapper::after {
            content: "";
            display: table;
            clear: both;
        }
        /* ----------------------------------------------------------------- */

        /* ----------- FRONT SIDE CONTENT LAYOUT ------------ */

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
            border: 5px solid #fff;
            box-shadow: 0 0.5mm 3mm rgba(0, 0, 0, 0.2);
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
            margin: 1.5mm 0; 
            font-size: 2mm;
            color: var(--text-dark);
        }

        .id-front .details p strong {
            display: inline-block;
            width: 20mm; 
            text-align: left;
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
            content: ":";
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
            padding: 2mm 0;
            font-size: 2mm;
            font-weight: 700;
            z-index: 10;
        }


        /* ------------ BACK SIDE CONTENT LAYOUT ------------ */
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
        // Check if the teacher photo is a valid Base64 string (meaning a real image was loaded)
        $isRealImage = strpos($teacherPhotoSrc, 'data:') === 0 && strlen($teacherPhotoSrc) > 100;
        // The logo_base64 from the setting object holds the Base64 data or SVG fallback
        $logoSrc = $setting->logo_base64; 
    @endphp

    <div class="card-wrapper">
        {{-- FRONT CARD --}}
        <div class="id-card id-front">

            <div class="header">
                {{-- 1. BASE64 IMAGE SHAPE LAYER (Z-index: 1) --}}
                {{-- $shapeBase64Url is the full CSS background style string prepared in the controller --}}
                <div class="header-background-shape" style="{!! $shapeBase64Url !!}">
                </div>

                {{-- 2. HEADER CONTENT LAYER (Z-index: 11) --}}
                <div class="header-content">
                    {{-- SCHOOL LOGO: Base64 Source --}}
                    <img src="{!! $logoSrc !!}" alt="School Logo" class="school-logo">

                    <h2>{{ $setting->school_name ?? 'BLUE BIRD SCHOOL' }}</h2>
                    <div style="font-size: 2mm; font-weight: 500;">STAFF ID CARD</div>
                </div>
            </div>

            <div class="photo-box">
                @if ($isRealImage)
                {{-- TEACHER PHOTO: Base64 Source --}}
                <img src="{!! $teacherPhotoSrc !!}" style="width: 100%; height: 100%; object-fit: cover;" alt="Teacher Photo">
                @else
                {{-- Placeholder using controller-generated SVG or simple div --}}
                <div class="photo-placeholder" style="background-color: var(--primary-blue);">
                    {{ substr($teacher->name ?? 'T', 0, 1) }}
                </div>
                @endif
            </div>

            <div class="details">
                <p><strong>Employee ID</strong> <span>{{ $teacher->joining_number ?? 'N/A' }}</span></p>
                <p><strong>Designation</strong> <span>{{ $teacher->designation ?? 'Teacher' }}</span></p>
                <p><strong>Teacher Name</strong> <span>{{ $teacher->name ?? 'Unknown' }}</span></p>
                {{-- Format date using Carbon --}}
                <p><strong>Joining Date</strong> <span>{{ \Carbon\Carbon::parse($teacher->joining_date)->format('d M, Y') ?? 'N/A' }}</span></p>
                <p><strong>Emergency</strong> <span>{{ $teacher->emergency_contact ?? $setting->phone_number ?? 'N/A' }}</span></p>
            </div>

            <div class="card-footer">
                {{ $setting->address ?? 'Shubid-Bazar, Sylhet' }}
            </div>
        </div>

        {{-- BACK CARD --}}
        <div class="id-card id-back">
            <div class="header">
                <h2>{{ $setting->school_name ?? 'BLUE BIRD SCHOOL' }}</h2>
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
</body>

</html>  