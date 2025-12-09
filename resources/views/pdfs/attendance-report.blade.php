<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - {{ $startDate }} to {{ $endDate }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 5mm 20mm;
            background-color: #ffffff;
            color: #333333;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 8mm;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 5mm;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #1a202c;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            margin: 2px 0;
            font-size: 11px;
            color: #4a5568;
        }
        .contact-info {
            margin-top: 5mm;
            font-style: italic;
        }
        .header img {
             /* Added style for clear placement of the logo */
            display: block; 
            margin: 0 auto 5px auto; 
        }
        table {
            width: 100%;
            border-collapse: collapse;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            page-break-inside: auto;
        }
        thead {
            display: table-header-group;
        }
        th, td {
            border: 1px solid #e0e0e0;
            padding: 8px 10px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #2d3748;
            color: #ffffff;
            font-weight: 600;
            text-align: center;
            text-transform: uppercase;
            font-size: 10px;
        }
        td.text-center {
            text-align: center;
        }
        .percentage {
            padding: 3px 6px;
            border-radius: 4px;
            font-weight: 600;
            display: inline-block;
        }
        .bg-green-100 { background-color: #48bb78; color: #ffffff; }
        .bg-yellow-100 { background-color: #ecc94b; color: #ffffff; }
        .bg-red-100 { background-color: #e53e3e; color: #ffffff; }
        @page {
            margin: 15mm;
            size: A4;
        }
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .header {
                border-bottom: 1px solid #e0e0e0;
            }
            th {
                background-color: #2d3748 !important;
            }
            .percentage {
                -webkit-print-color-adjust: exact;
            }
            tbody tr {
                page-break-inside: avoid;
            }
            tbody tr:nth-child(30n) {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        {{-- SCHOOL LOGO PLACEMENT (Moved to the top) --}}
        @if (!empty($schoolLogoUrl))
            <img src="{{ $schoolLogoUrl }}" alt="School Logo" style="height: 60px; margin-bottom: 10px;">
        @endif
        
        <h1>{{ $setting->school_name }}</h1>
        
        <div class="contact-info">
            {{-- Adjusted to display address and phone number cleanly --}}
            @if ($setting->address)
                <p>{{ $setting->address }}</p>
            @endif
            @if ($setting->phone_number)
                <p>Phone: {{ $setting->phone_number }}</p>
            @endif
        </div>

        {{-- Report Specific Details --}}
        <p>Attendance Report: **{{ $class->class_name }} - {{ $section->name }} - {{ $group->name }}**</p>
        <p>Session: {{ $session->name }}</p>
        <p>Date Range: **{{ $startDate }} to {{ $endDate }}**</p>
        @if ($setting->principal_name)
            <p>Principal: {{ $setting->principal_name }}</p>
        @endif
        <p>Generated on: ({{ now()->timezone('Asia/Dhaka')->format('h:i A') }})</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Student ID</th>
                <th>Roll No.</th>
                <th class="text-center">Recorded Days</th>
                <th class="text-center">Present</th>
                <th class="text-center">Absent</th>
                <th class="text-center">Late</th>
                <th class="text-center">Attendance %</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reportData as $student)
                <tr>
                    <td>{{ $student['name'] }}</td>
                    <td>{{ $student['admission_number'] }}</td>
                    <td>{{ $student['roll_number'] }}</td>
                    <td class="text-center">{{ $student['recorded_days'] }}</td>
                    <td class="text-center">{{ $student['present_days'] }}</td>
                    <td class="text-center">{{ $student['absent_days'] }}</td>
                    <td class="text-center">{{ $student['late_days'] }}</td>
                    <td class="text-center">
                        <span class="{{ $student['percentage_class'] }} percentage">
                            {{ $student['attendance_percentage'] }}%
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>