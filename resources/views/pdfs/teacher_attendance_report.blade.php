<!DOCTYPE html>
<html>
<head>
    <title>Teacher Attendance Report - {{ $startDate }} to {{ $endDate }}</title>
    <style>
        body { font-family: sans-serif; margin: 0; padding: 0; }
        .container { padding: 30px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; color: #333; }
        .header p { margin: 5px 0 0; font-size: 16px; color: #555; }
        .date-section { margin-top: 30px; /* break-before: always; */ }
        .date-title { font-size: 18px; color: #1e40af; border-bottom: 2px solid #1e40af; padding-bottom: 5px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 11px; }
        th { background-color: #f2f2f2; color: #333; text-transform: uppercase; }
        .status-Present { color: green; font-weight: bold; }
        .status-Absent { color: red; font-weight: bold; }
        .status-Leave { color: orange; font-weight: bold; }
        .status-Half-Day { color: blue; font-weight: bold; }
        .footer { margin-top: 40px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 10px; color: #777; text-align: right; }
        
        /* New styles for School Info (using $setting) */
        .school-info { margin-bottom: 10px; }
        .school-info h1 { font-size: 28px; color: #1e40af; }
        .school-info p { font-size: 14px; color: #4b5563; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            {{-- SCHOOL LOGO PLACEMENT --}}
            @if (!empty($schoolLogoUrl))
                <img src="{{ $schoolLogoUrl }}" alt="School Logo" style="height: 60px; display: block; margin: 0 auto 5px auto;">
            @endif
            {{-- END LOGO PLACEMENT --}}
            
            <div class="school-info">
                <h1 style="color: #1e3a8a;">
                    {{ $setting->school_name ?? 'School Name Not Set' }} 🏫
                </h1>
                <p style="margin-top: -5px;">
                    {{ $setting->address ?? 'Address not available' }}
                </p>
                <hr style="border: 0.5px solid #ddd; margin: 10px 0;">
            </div>

            <h1>Teacher Attendance Report (Date Range)</h1>
            <p>From: <strong>{{ $startDate }}</strong> To: <strong>{{ $endDate }}</strong></p>
        </div>

        @forelse($groupedAttendance as $date => $attendances)
            <div class="date-section">
                <div class="date-title">Attendance for **{{ \Carbon\Carbon::parse($date)->format('l, F d, Y') }}**</div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 25%;">Teacher Name</th>
                            <th style="width: 10%;">ID</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 10%;">In Time</th>
                            <th style="width: 10%;">Out Time</th>
                            <th style="width: 30%;">Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- ❗ CORE FIX: Loop through ALL teachers for the day --}}
                        @foreach($allTeachers as $index => $teacher)
                            @php
                                // Find the attendance record for the current teacher on this date
                                $record = $attendances->firstWhere('teacher_id', $teacher->id);
                                
                                // Set defaults if no record exists (meaning 'Absent')
                                $status = optional($record)->status ?? 'Absent';
                                $inTime = optional($record)->in_time ? \Carbon\Carbon::parse(optional($record)->in_time)->format('h:i A') : '-';
                                $outTime = optional($record)->out_time ? \Carbon\Carbon::parse(optional($record)->out_time)->format('h:i A') : '-';
                                $note = optional($record)->note ?? '-';
                            @endphp
                            
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $teacher->name }}</td>
                                <td>{{ $teacher->joining_number }}</td>
                                <td class="status-{{ str_replace(' ', '-', $status) }}">
                                    {{ $status }}
                                </td>
                                <td>{{ $inTime }}</td>
                                <td>{{ $outTime }}</td>
                                <td>{{ $note }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <p style="text-align: center; font-size: 16px; color: #777; margin-top: 30px;">
                No attendance records found between {{ $startDate }} and {{ $endDate }}.
            </p>
        @endforelse

        <div class="footer">
            Report generated on: {{ $reportGenerated }}
        </div>
    </div>
</body>
</html>