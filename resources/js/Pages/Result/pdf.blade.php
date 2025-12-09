<!DOCTYPE html>
<html>
<head>
    <title>Student Results - Half-Yearly Exam</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 0 40px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 6px;
            font-size: 13px;
        }
        th {
            background-color: #f2f2f2;
        }
        .header {
            text-align: center;
            margin-bottom: 8px;
        }
        .school-logo {
            height: 70px;
        }
        .school-name {
            font-size: 20px;
            font-weight: bold;
            margin: 6px 0;
        }
        .student-info {
            margin-bottom: 10px;
            font-size: 14px;
        }
        .overall-status {
            font-weight: bold;
        }
        .pass {
            color: green;
        }
        .fail {
            color: red;
        }
        .signature-block {
            text-align: right;
            margin-top: 30px;
        }
        .signature-block img {
            height: 60px;
        }
        .signature-name {
            border-top: 1px solid #555;
            padding-top: 4px;
            font-size: 12px;
            display: inline-block;
            margin-top: 6px;
        }
        h3 {
            margin-top: 20px;
            font-size: 16px;
            border-bottom: 1px solid #999;
            padding-bottom: 4px;
        }
    </style>
</head>
<body>

    {{-- Global Header --}}
    <div class="header">
        @if(!empty($schoolLogoUrl))
            <img src="{{ $schoolLogoUrl }}" alt="School Logo" class="school-logo">
        @endif
        <div class="school-name">{{ $schoolName }}</div>
       
        <div>Results for Half-Yearly Exam</div>
        <div>Session: {{ $exam->session->name ?? 'N/A' }}</div>
        <div>Class: {{ $student->className->name ?? 'N/A' }} - Section: {{ $student->section->name ?? 'N/A' }}</div>
    </div>

    {{-- Student Result Block --}}
    <div class="student-info">
        <strong>{{ $student->first_name }} {{ $student->last_name }}</strong> (Roll No: {{ $student->roll_no }})<br>
        Admission No: {{ $student->admission_number ?? 'N/A' }}
    </div>

    <h3>Subject-wise Marks</h3>
    <table>
        <thead>
            <tr>
                <th>Subject</th>
                <th>Marks</th>
                <th>Total</th>
                <th>%</th>
                <th>Grade</th>
                <th>GP</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjectDetails as $subject)
            <tr>
                <td>{{ $subject['subject_name'] }}</td>
                <td>{{ $subject['marks_obtained'] ?? 'N/A' }}</td>
                <td>{{ $subject['total_marks'] ?? 'N/A' }}</td>
                <td>{{ $subject['percentage'] ?? 'N/A' }}%</td>
                <td>{{ $subject['letter_grade'] ?? 'N/A' }}</td>
                <td>{{ $subject['grade_point'] ?? 'N/A' }}</td>
                <td><span class="{{ strtolower($subject['pass_status']) }}">{{ $subject['pass_status'] ?? 'N/A' }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Overall Result</h3>
    <table>
        <tbody>
            <tr>
                <th>Total Obtained Marks</th>
                <td>{{ $overallTotalObtained }} / {{ $overallTotalPossible }}</td>
            </tr>
            <tr>
                <th>Overall Percentage</th>
                <td>{{ $overallPercentage }}%</td>
            </tr>
            <tr>
                <th>Overall GPA</th>
                <td>{{ $overallGPA }}</td>
            </tr>
            <tr>   
                <th>Overall Letter Grade</th>
                <td>{{ $overallLetterGrade }}</td>
            </tr>
            <tr>
                <th>Overall Status</th>
                <td><span class="overall-status {{ strtolower($overallStatus) }}">{{ $overallStatus }}</span></td>
            </tr>
        </tbody>
    </table>

    {{-- Principal Signature --}}
    <div class="signature-block">
        @if(!empty($principalSignatureUrl))
            <img src="{{ $principalSignatureUrl }}" alt="Principal Signature">
        @endif
        <div class="signature-name">{{ $principalName ?? 'Principal' }}</div>
    </div>

</body>
</html>
