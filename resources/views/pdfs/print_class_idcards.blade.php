<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student ID Cards</title>
    <style>
        :root {
            --primary-orange: #000080;
            --dark-orange: #000080;
            --card-bg: #fff;
            --text-light: #fff;
            --text-dark: #1a1a1a;
            --border-radius: 15px;
        }

        body {
            margin: 0;
            padding: 30px 20px;
            background: #f5f5f5;
            font-family: Arial, Helvetica, sans-serif;
        }

        .student-row {
            width: 100%;
            text-align: center;
            margin-bottom: 60px;
            page-break-after: always;
            overflow: hidden;
        }

        .id-card {
            width: 440px;
            height: 700px;
            border-radius: var(--border-radius);
            background: var(--card-bg);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
            overflow: hidden;
            position: relative;
            border: 1px solid #e0e0e0;
            display: inline-block;
            margin: 0 20px;
            vertical-align: top;
        }

        /* FRONT */
        .id-front .header {
            height: 180px;
            position: relative;
            background: var(--primary-orange);
        }

        .id-front .header-background-shape {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-size: cover;
            background-position: center bottom;
            background-repeat: no-repeat;
            z-index: 1;
        }

        .id-front .header-content {
            position: absolute;
            top: 0; left: 0; width: 100%;
            text-align: center;
            color: var(--text-light);
            z-index: 11;
        }

        .id-front .header-content h2 {
            margin-top: 100px;
            font-size: 26px;
            font-weight: 700;
        }

        .id-front .school-logo {
            position: absolute;
            top: 25px;
            left: 50%;
            transform: translateX(-50%);
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
            object-fit: cover;
            z-index: 12;
        }

        .id-front .photo-box {
            width: 130px;
            height: 130px;
            border: 5px solid white;
            border-radius: 50%;
            overflow: hidden;
            margin: -1px auto 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
            z-index: 12;
            position: relative;
        }

        .id-front .photo-placeholder {
            width: 100%; height: 100%;
            background: var(--primary-orange);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            font-weight: bold;
            border-radius: 50%;
        }

        .details {
            font-size: 14px;
            
            

        }

        .detail-row {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            font-family:  'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 700;

        }

        .detail-label {
            min-width: 130px;    /* adjust until it matches your screenshot */
        }

        .detail-colon {
            width: 15px;         /* keeps all colons in one vertical line */
            text-align: center;
        }

        .detail-value {
            flex: 1;
        }


        .id-front .card-footer {
            position: absolute;
            bottom: 0; left: 0; width: 100%;
            background: var(--dark-orange);
            color: white;
            text-align: center;
            padding: 12px 0;
            font-size: 16px;
            font-weight: 700;
        }

        /* BACK */
        .id-back .header {
            padding: 50px 30px 20px;
            text-align: center;
        }

        .id-back .header h2 {
            color: var(--dark-orange);
            font-size: 24px;
            margin: 0;
        }

        .id-back .section-title {
            margin: 30px auto 15px;
            width: fit-content;
            border-bottom: 2px solid var(--dark-orange);
            padding-bottom: 5px;
            font-weight: 700;
            font-size: 18px;
            color: #333;
        }

        .id-back .section ul {
            padding-left: 50px;
            font-size: 14px;
            line-height: 1.8;
            color: #333;
            text-align: justify;
        }

        .id-back .signature-area {
            margin-top: 100px;
            text-align: center;
        }

        .id-back .signature-area h4 {
            margin: 0 0 10px;
            color: #333;
            font-weight: 500;
        }

        .id-back .footer-sign {
            color: var(--dark-orange);
            font-weight: bold;
            font-size: 18px;
            border-top: 1px solid #333;
            display: inline-block;
            padding-top: 8px;
            min-width: 220px;
        }
    </style>
</head>

<body>

@php
    // Original shape logic is kept, though it may not be necessary if the shape is complex and not a real image.
    // If 'shape.png' exists and is small, this is okay. If not, consider making this a background-color.
    $shapePath = public_path('assets/image/shape.png');
    $shapeBase64Url = 'background-color: var(--primary-orange);';
    if (file_exists($shapePath)) {
        $data = base64_encode(file_get_contents($shapePath));
        $shapeBase64Url = "background-image: url('data:image/png;base64,{$data}'); background-size: cover; background-position: center bottom;";
    }
@endphp

@foreach($students as $student)
    @php
        // The controller ensures a base64 string is present for $student->image_base64
        // Check if it's a real base64 image (not the placeholder URL)
        $isRealImage = str_starts_with($student->image_base64 ?? '', 'data:');
    @endphp

    <div class="student-row">

        <div class="id-card id-front">
            <div class="header">
                <div class="header-background-shape" style="{{ $shapeBase64Url }}"></div>
                
                <div class="header-content">
                    {{-- FIX 1: Use the logo_base64 property set in the controller, which handles default/placeholder logic --}}
                    <img src="{!! $setting->logo_base64 !!}" alt="School Logo" class="school-logo">

                    <h2>{{ $setting->school_name ?? 'ABCD School' }}</h2>
                </div>
            </div>

            <div class="photo-box">
                {{-- FIX 2: Check for real image type and ensure unescaped output --}}
                @if($isRealImage)
                    <img src="{!! $student->image_base64 !!}" style="width:100%;height:100%;object-fit:cover;">
                @else
                    <div class="photo-placeholder">
                        {{ substr($student->name ?? 'S', 0, 1) }}
                    </div>
                @endif
            </div>

            
<div class="details">
    <div class="detail-row">
        <span class="detail-label">Student ID</span>
        <span class="detail-colon" style="margin-left: 39px;">:</span>
        <span class="detail-value">{{ $student->admission_number ?? 'N/A' }}</span>
    </div>

    <div class="detail-row" style="margin-left: -35px;">
        <span class="detail-label">Roll Number</span>
        <span class="detail-colon" style="margin-left: 28px;">:</span>
        <span class="detail-value">{{ $student->roll_number ?? 'N/A' }}</span>
    </div>

    <div class="detail-row" style="margin-left: 44px;">
        <span class="detail-label">Name</span>
        <span class="detail-colon" style="margin-left: 70px;">:</span>
        <span class="detail-value">{{ $student->name ?? 'Unknown' }}</span>
    </div>

    <div class="detail-row" style="margin-left: 10px;">
        <span class="detail-label" >Class</span>
        <span class="detail-colon" style="margin-left: 65px;">:</span>
        <span class="detail-value">
            {{ optional($student->className)->class_name ?? 'N/A' }}
            @if($student->section && $student->section->name)
                ({{ $student->section->name }})
            @endif
        </span>
    </div>

    <div class="detail-row" style="margin-left: 30px;">
        <span class="detail-label">Guardian</span>
        <span class="detail-colon" style="margin-left: 55px;">:</span>
        <span class="detail-value">{{ $student->parent_name ?? 'N/A' }}</span>
    </div>

    <div class="detail-row" style="margin-left: 30px;">
        <span class="detail-label">Emergency Call</span>
        <span class="detail-colon" style="margin-left: 15px;">:</span>
        <span class="detail-value">{{ $setting->phone_number ?? 'N/A' }}</span>
    </div>
</div>




            <div class="card-footer">
                {{ $setting->address ?? 'Shubid-Bazar, Sylhet' }}
            </div>
        </div>

        <div class="id-card id-back">
            {{-- ... back card content is unchanged and correct ... --}}
            <div class="header">
                <h2>{{ $setting->school_name ?? 'ABCD School' }}</h2>
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
                <div class="footer-sign">{{ $setting->principal_name ?? 'Principal Name' }}</div>
            </div>
        </div>

    </div>
@endforeach

</body>
</html>