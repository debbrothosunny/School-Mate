<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Transcript</title>
    <style>
        /* Global Styles */
        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 0;
            font-size: 10pt;
            color: #2c3e50;
            background-color: #fff;
            line-height: 1.5;
        }
        .container {
            width: 90%;
            margin: 0 auto;
            padding: 30px 0;
        }
        @page {
            margin: 40px 30px;
            size: A4;
        }
        .student-section {
            margin-bottom: 30px;
            border: 1px solid #dcdcdc;
            padding: 20px;
            background-color: #ffffff;
            page-break-inside: avoid;
        }

        /* Header */
        .page-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 4px solid #34495e;
        }
        .page-header img {
            max-width: 100px;
            height: auto;
            margin-bottom: 8px;
        }
        .page-header h1 {
            color: #34495e;
            margin: 0;
            font-size: 1.6em;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .page-header p {
            margin: 4px 0;
            color: #7f8c8d;
            font-size: 0.9em;
            font-style: italic;
        }

        /* === PERFECT STUDENT DETAILS (EXACT MATCH TO REFERENCE) === */
        .student-info-perfect {
            margin: 20px 0;
            border: 1px solid #ddd;
            border-left: 6px solid #27ae60;
            background-color: #f9f9f9;
            font-family: 'Times New Roman', serif;
            font-size: 10.5pt;
        }
        .student-info-header {
            background-color: #27ae60;
            color: white;
            padding: 8px 15px;
            font-weight: bold;
            font-size: 11pt;
            border-bottom: 1px solid #1e8449;
        }
        .student-info-header h2 {
            margin: 0;
            font-size: 11pt;
            font-weight: bold;
        }
        .student-info-body {
            padding: 12px 15px;
            background-color: #ffffff;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            line-height: 1.4;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-row .label {
            font-weight: bold;
            color: #2c3e50;
            width: 100px;
            flex: 0 0 100px;
            text-align: left;
        }
        .info-row .value {
            color: #34495e;
            flex: 1;
            text-align: left;
            padding-left: 10px;
        }
        .info-row .label:empty,
        .info-row .value:empty {
            visibility: hidden;
        }

        /* Section Title */
        .section-title {
            font-size: 1.15em;
            color: #34495e;
            margin: 15px 0 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid #bdc3c7;
            font-weight: 600;
        }

        /* Marks Table */
        .marks-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #bdc3c7;
            margin-bottom: 20px;
        }
        .marks-table th,
        .marks-table td {
            border: 1px solid #e0e0e0;
            padding: 8px 6px;
            text-align: center;
            font-size: 0.9em;
        }
        .marks-table th {
            background-color: #34495e;
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .marks-table th:first-child,
        .marks-table td:first-child {
            text-align: left;
        }
        .marks-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        /* Overall Result */
        .overall-result {
            margin-top: 30px;
            padding: 15px;
            background-color: #f4f6f9;
            border: 3px solid #3498db;
            border-radius: 8px;
            width: 40%;
            float: left;
            box-sizing: border-box;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .overall-result h3 {
            margin: 0 0 10px;
            color: #3498db;
            font-size: 1.1em;
            font-weight: 700;
            text-align: center;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
            text-transform: uppercase;
        }
        .overall-result table {
            width: 100%;
            margin-top: 5px;
            font-size: 0.95em;
        }
        .overall-result th,
        .overall-result td {
            padding: 5px 0;
            text-align: left;
        }
        .overall-result th {
            font-weight: 500;
            color: #5d6d7e;
            width: 55%;
        }
        .overall-result td strong {
            color: #34495e;
            font-size: 1.05em;
        }

        /* Status */
        .overall-status {
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 15px;
            display: inline-block;
            min-width: 60px;
            text-align: center;
            text-transform: uppercase;
            font-size: 0.9em;
        }
        .pass {
            color: #1e8449;
            background-color: #e8f8f5;
            border: 1px solid #1e8449;
        }
        .fail {
            color: #c0392b;
            background-color: #fdedec;
            border: 1px solid #c0392b;
        }

        /* Footer */
        .page-footer {
            clear: both;
            margin-top: 50px;
            padding-top: 20px;
        }
        .signature-group {
            width: 100%;
            overflow: hidden;
            padding-bottom: 40px;
        }
        .signature-box {
            width: 200px;
            padding-top: 5px;
            border-top: 1px dashed #7f8c8d;
            text-align: center;
            margin: 0;
        }
        .signature-box.left {
            float: left;
            margin-right: 5%;
            width: 25%;
        }
        .signature-box.center {
            float: left;
            width: 25%;
        }
        .signature-box p {
            margin: 2px 0;
            font-size: 0.85em;
            font-weight: 600;
            color: #5d6d7e;
        }
        .copyright-notice {
            clear: both;
            margin-top: 20px;
            border-top: 1px solid #e0e0e0;
            padding-top: 15px;
            font-size: 0.8em;
            text-align: center;
            color: #95a5a6;
        }
        .copyright-notice .developer-info {
            font-weight: 600;
            color: #5d6d7e;
            padding-left: 5px;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* Print Optimization */
        @media print {
            .student-info-perfect {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        @if(empty($studentResultsData))
            <div class="no-results" style="text-align: center; color: #7f8c8d;">
                <h3>No Results Found</h3>
                <p>No students found for the selected criteria or no marks entered yet.</p>
            </div>
        @else
            @foreach($studentResultsData as $studentResult)
                <div class="student-section clearfix">
                    <div class="page-header">
                        @if($schoolLogoUrl)
                            <img src="{{ $schoolLogoUrl }}" alt="School Logo">
                        @else
                            <p style="color: #7f8c8d; font-size: 0.9em;">[Logo Not Available]</p>
                            <?php \Log::warning('PDF LOGO CHECK: No valid logo URL provided', ['schoolLogoUrl' => $schoolLogoUrl]); ?>
                        @endif
                        <h1>{{ $schoolName ? strtoupper($schoolName) : 'SCHOOL NAME' }}</h1>
                        <p><strong>ACADEMIC TRANSCRIPT</strong></p>
                        <p>Exam: {{ $exam['exam_name'] ?? 'Annual Examination' }} (Session: {{ date('Y') }})</p>
                    </div>

                    <!-- === PERFECTED STUDENT DETAILS (EXACT MATCH) === -->
                    <div class="student-info-perfect">
                        <div class="student-info-header">
                            <h2>Student Details</h2>
                        </div>
                        <div class="student-info-body">
                            <div class="info-row">
                                <span class="label">Name:</span>
                                <span class="value">{{ $studentResult['student']['name'] ?? 'N/A' }}</span>
                                <span class="label">Class:</span>
                                <span class="value">{{ $studentResult['student']['className']['class_name'] ?? 'N/A' }} ({{ $studentResult['student']['section']['name'] ?? 'N/A' }})</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Roll No:</span>
                                <span class="value">{{ $studentResult['student']['roll_number'] ?? 'N/A' }}</span>
                                <span class="label">Student ID:</span>
                                <span class="value">{{ $studentResult['student']['admission_number'] ?? 'N/A' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Group:</span>
                                <span class="value">{{ $studentResult['student']['group']['name'] ?? 'None' }}</span>
                                <span class="label"></span>
                                <span class="value"></span>
                            </div>
                        </div>
                    </div>

                    <h3 class="section-title">Subject-wise Performance</h3>
                    <table class="marks-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Subj.</th>
                                <th>Obj.</th>
                                <th>Prac.</th>
                                <th>Obtained Marks</th>
                                <th>Total Marks</th>
                                <th>Pct. (%)</th>
                                <th>GP</th>
                                <th>Grade</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($studentResult['subjects_data'] as $subject)
                                <tr>
                                    <td style="text-align: left;">{{ $subject['subject_name'] ?? 'N/A' }}</td>
                                    <td>{{ $subject['subjective_marks'] ?? '-' }}</td>
                                    <td>{{ $subject['objective_marks'] ?? '-' }}</td>
                                    <td>{{ $subject['practical_marks'] ?? '-' }}</td>
                                    <td>{{ $subject['marks_obtained'] ?? '-' }}</td>
                                    <td>{{ $subject['total_marks'] ?? '-' }}</td>
                                    <td>{{ $subject['percentage'] ? number_format($subject['percentage'], 2) : '-' }}</td>
                                    <td>{{ $subject['grade_point'] ? number_format($subject['grade_point'], 2) : '-' }}</td>
                                    <td>{{ $subject['letter_grade'] ?? 'N/A' }}</td>
                                    <td><span class="overall-status {{ strtolower($subject['pass_status'] ?? 'N/A') }}">{{ $subject['pass_status'] ?? 'N/A' }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="overall-result">
                        <h3>Result Overview</h3>
                        <table>
                            <tbody>
                                <tr>
                                    <th>Total Marks Obtained</th>
                                    <td><strong>{{ $studentResult['overall_total_obtained'] ?? 0 }}</strong> /
                                        {{ $studentResult['overall_total_possible'] ?? 0 }}</td>
                                </tr>
                                <tr>
                                    <th>Overall Percentage</th>
                                    <td><strong>{{ $studentResult['overall_percentage'] ? number_format($studentResult['overall_percentage'], 2) : 0 }}%</strong></td>
                                </tr>
                                <tr>
                                    <th>Overall GPA</th>
                                    <td><strong>{{ $studentResult['overall_gpa'] ? number_format($studentResult['overall_gpa'], 2) : 0 }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Overall Letter Grade</th>
                                    <td><strong>{{ $studentResult['overall_letter_grade'] ?? 'N/A' }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Final Result Status</th>
                                    <td><span class="overall-status {{ strtolower($studentResult['overall_status'] ?? 'N/A') }}">{{ $studentResult['overall_status'] ?? 'N/A' }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="page-footer clearfix">
                        <div class="signature-group">
                            <div class="signature-box left">
                                <p>Guardian's Signature</p>
                            </div>
                            <div class="signature-box center">
                                <p>Class Teacher's Signature</p>
                            </div>
                        </div>
                        <div class="copyright-notice">
                            Generated on: {{ date('F j, Y') }}.
                            <span class="developer-info">© All Rights Reserved. Biddaloy is a product of Smith IT.</span>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</body>
</html>  