<!DOCTYPE html>
<html>

<head>
    <title>Student ID Card (Front & Back - Orange Design)</title>
    <style>
    /* --- VARIABLES --- */
    :root {
        --primary-orange: #000080;
        /* Main Orange color */
        --dark-orange: #000080;
        /* Darker Orange for contrast/footer */
        --card-bg: #fff;
        --photo-bg: #ffe6a7;
        /* Light yellow background for student initial */
        --text-dark: #1a1a1a;
        --text-light: #fff;
        /* White text for the header */
        --text-muted: #666;
        --border-radius: 15px;
    }

    /* --- BASE STYLES --- */
    body {
        /* Assuming --gray is defined or just use a standard light color */
        background: #f0f0f0;
        margin: 0;
        font-family: Arial, Helvetica, sans-serif;
    }

    .card-wrapper {
        width: 100%;
        text-align: center;
        padding: 40px 0;
        display: flex;
        justify-content: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .id-card {
        width: 340px;
        height: 620px;
        border-radius: var(--border-radius);
        margin: 0;
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
        background: var(--card-bg);
        display: inline-block;
        vertical-align: top;
        overflow: hidden;
        position: relative;
        border: 1px solid #e0e0e0;
    }

    /* ----------- FRONT SIDE CONTENT LAYOUT ------------ */

    .id-front .header {
        background: none;
        padding: 0;
        height: 180px;
        /* CRITICAL: Fixed height */
        position: relative;
        /* CRITICAL: Positioning context */
        z-index: 10;
    }

    /* --- Element for the background shape image (Base64) --- */
    .id-front .header-background-shape {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;

        /* Ensure these are present in your stylesheet */
        background-size: cover;
        background-position: center bottom;
        background-repeat: no-repeat;
        z-index: 1;
        /* Lowest layer */
    }

    /* --- Content container for logo and text, sits above the shape --- */
    .id-front .header-content {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        text-align: center;
        color: var(--text-light);
        z-index: 11;
        /* Above the background shape */
    }

    /* School Name Text */
    .id-front .header-content h2 {
        margin-top: 100px;
        font-size: 20px;
        font-weight: 700;
        color: var(--text-light);
        /* White text */
    }

    /* School Logo Position */
    .id-front .header .school-logo {
        position: absolute;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        width: 70px;
        height: 70px;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        object-fit: cover;
        z-index: 12;
    }

    .id-front .header .contact,
    .id-front .identity-label,
    .id-front .top-bar {
        display: none;
    }

    /* Student Photo Position/Style (Overlaps header) */
    .id-front .photo-box {
        width: 110px;
        height: 110px;
        border: 4px solid var(--card-bg);
        border-radius: 50%;
        margin: -45px auto 25px auto;
        /* Overlap header */
        overflow: hidden;
        z-index: 12;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        position: relative;
        margin-top: 10px;
        box-shadow: 0 10px 18px rgba(0, 0, 0, 0.35);

    }

    .id-front .photo-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 60px;
        color: var(--text-light);
        /* CRITICAL: Ensure initial text is visible */
        background-color: var(--primary-orange);
        /* CRITICAL: Set initial background color */
        border-radius: 50%;
        font-weight: 700;
    }

    /* Details Section */

    /* Details Section */
    /* ========== PERFECT ALIGNMENT FIX (THIS IS THE ONLY PART CHANGED) ========== */
    .id-front .details {
        padding: 10px 10px;

        
    }

    .id-front .details p {
        display: flex;
        align-items: center;
        margin: 18px 0;
        font-size: 15px;
        color: var(--text-dark);
    }

    .id-front .details p strong {
        display: inline-block;
        width: 135px;
        /* Fixed width for perfect alignment */
        text-align: left;
        font-weight: 500;
        color: #333;
    }

    .id-front .details p strong::after {
        content: "";
        color: #000;
    }

    /* --- 2. ADD THE NEW RULE (to span::before) --- */
    .id-front .details p span::before {
        content: ": "; /* Adds the colon and a space */
        color: var(--text-dark, #000); /* Use dark text color */
        padding-right: 5px; /* Adds space between the colon and the value */
    }
    
    

    .id-front .details p span {
        font-weight: 700;
        color: #000;
        margin-left: 8px;
    }



    /* Footer Bar with address */
    .id-front .card-footer {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        background: var(--dark-orange);
        color: var(--text-light);
        text-align: center;
        padding: 12px 0;
        font-size: 16px;
        font-weight: 700;
        z-index: 10;
        margin-top: 18px;
    }

    .id-front .bottom-bar {
        display: none;
    }

    /* ------------ BACK SIDE CONTENT LAYOUT (Colors adjusted to match theme) ------------ */
    .id-back .top-bar {
        background: var(--dark-orange);
    }

    .id-back .header {
    background: none;
    color: var(--text-dark);
    /* Adjust top padding to center content better */
    padding: 30px 30px 0 30px; 
    position: relative;
    /* Use less height if the section starts too low */
    height: 100px; 
    z-index: 10;
    text-align: center;
    }

    .id-back .header h2 {
        color: var(--dark-orange);
    }

    .id-back .section {
    padding: 0 45px; /* Add horizontal padding to center content */
    margin-top: 20px; /* Add space below the header */
    text-align: left; /* Ensure list items start neatly */
    }

    /* Ensure the title is centered above the list */
    .id-back .section-title {
        color: var(--text-dark);
        border-bottom: 2px solid var(--dark-orange); 
        padding-bottom: 3px;
        display: block; /* Change to block to allow margin/centering */
        width: fit-content; /* Only draw line under text */
        margin: 0 auto 10px auto; /* Center the title horizontally */
        font-weight: 700;
        text-align: center
    }


    .id-back .section ul {
        list-style-type: disc;
        margin: 15px 0 0 15px;
        padding-left: 10px;
        font-size: 14px;
        line-height: 1.6;
    }

    .id-back .signature-area h4 {
        color: var(--text-dark);
    }

    .id-back .footer-sign {
        color: var(--dark-orange);
    }

    .id-back .bottom-bar {
        background: var(--dark-orange);
    }

    .id-back .signature-area {
    /* Push the signature area down */
    margin-top: 100px; /* Adjust this value to position it correctly */
    padding: 0 45px;
    text-align: center;
}

.id-back .signature-area h4 {
    color: var(--text-dark);
    font-weight: 500;
    margin-bottom: 5px;
}

.id-back .footer-sign {
    color: var(--dark-orange); 
    font-weight: bold;
    border-top: 1px solid var(--text-dark); /* Add a line for the signature */
    display: inline-block;
    padding-top: 3px;
    font-size: 16px;
}
    </style>
</head>

<body>
    @php
    // Define the full path to the image
    $shapePath = public_path('assets/image/shape.png');

    // Check if the file exists and generate the Base64 string
    $shapeBase64Url = '';
    if (file_exists($shapePath)) {
    $shapeData = file_get_contents($shapePath);
    // Added check for file_get_contents failure
    if ($shapeData !== false) {
    $shapeData = base64_encode($shapeData);
    // CRITICAL: Ensure the semicolon is included in the string
    $shapeBase64Url = "background-image: url('data:image/png;base64,{$shapeData}');";
    }
    }
    @endphp

    <div class="card-wrapper">
        <div class="id-card id-front">

            <div class="header">

                {{-- 1. BASE64 IMAGE SHAPE LAYER (Z-index: 1) --}}
                <div class="header-background-shape" style="{{ $shapeBase64Url }}">
                </div>

                {{-- 2. HEADER CONTENT LAYER (Z-index: 11) --}}
                <div class="header-content">
                    {{-- SCHOOL LOGO (Base64) --}}
                    @php
                    $logoPath = public_path($setting->school_logo_url ?? 'path/to/default/logo.png');
                    $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' .
                    base64_encode(file_get_contents($logoPath)) : 'https://via.placeholder.com/70/fff/000?text=LOGO';
                    @endphp
                    <img 
                    src="{!! $setting->image_source['base64'] 
                            ?? 'https://via.placeholder.com/70/fff/000?text=LOGO' !!}" 
                    alt="School Logo" 
                    class="school-logo">


                    <h2>{{ $setting->school_name ?? 'ABCD School' }}</h2>
                </div>
            </div>

            <div class="photo-box">
                @php
                $imageBase64 = $student->image_source['base64'] ?? null;
                $isRealImage = strlen($imageBase64) > 100;
                @endphp

                @if ($isRealImage)
                <img src="{!! $imageBase64 !!}" style="width: 100%; height: 100%; object-fit: cover;">
                @else
                <div class="photo-placeholder">
                    {{ substr($student->name ?? 'S', 0, 1) }}
                </div>
                @endif
            </div>

            <!-- PERFECTLY ALIGNED DETAILS -->
            <div class="details">
                <p><strong>Student ID</strong> <span>{{ $student->admission_number ?? '321' }}</span></p>
                <p class="custom-roll-style" style="margin-right: 45px;">
                    <strong>Roll Number</strong> <span>{{ $student->roll_number ?? '123' }}</span>
                </p>
                <p><strong>Student Name</strong> <span>{{ $student->name ?? 'Unknown' }}</span></p>
                <p class="custom-emergency-style" style="margin-right: 5px;"><strong>Class</strong> <span>{{ $student->className->class_name ?? 'None' }} @if($student->section &&
                        $student->section->name)({{ $student->section->name }})@endif</span></p>
                <p class="custom-guardian-style" style="margin-left: 8px;"><strong>Father/Guardian</strong><span>{{ $student->parent_name ?? 'John Doe' }}</span></p>
                <p class="custom-emergency-style" style="margin-left: 34px;"><strong>Emergency</strong> <span>{{ $setting->phone_number ?? '01615338863' }}</span></p>
            </div>

            <div class="card-footer">
                {{ $setting->address ?? 'Shubid-Bazar' }}
            </div>
        </div>

        <div class="id-card id-back">
            <div class="top-bar"></div>
            <div class="header">
                <h2>{{ $setting->school_name ?? 'Your School Name' }}</h2>
            </div>
            <div class="section">
                <div class="section-title">TERMS AND CONDITIONS</div>
                <ul>
                    <li>This card must be presented upon request within campus.</li>
                    <li>Report loss of card to the office immediately.</li>
                    <li>Misuse will result in disciplinary measures.</li>
                </ul>
            </div>
            <div class="signature-area">
                <h4>PRINCIPAL SIGNATURE</h4>
                <div class="footer-sign">{{ $setting->principal_signature ?? 'Principal Name' }}</div>
            </div>
            <div class="bottom-bar"></div>
        </div>
    </div>
</body>

</html>