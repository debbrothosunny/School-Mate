<!DOCTYPE html>
<html>
<head>
    <title>Student Results - {{ $exam['exam_name'] ?? 'Exam' }}</title>
    <style>
        /* Add your CSS for PDF styling here */
        body { font-family: sans-serif; margin: 20px; }
        .container { width: 100%; max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #333; margin: 0; padding-bottom: 5px; border-bottom: 2px solid #eee; }
        .header p { margin: 5px 0; color: #555; font-size: 0.9em; }
        .student-info { margin-bottom: 25px; border: 1px solid #ddd; padding: 15px; background-color: #f9f9f9; border-radius: 5px; }
        .student-info h2 { margin-top: 0; margin-bottom: 10px; color: #007bff; font-size: 1.2em; }
        .student-info p { margin: 5px 0; font-size: 0.95em; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; table-layout: fixed; } /* Added table-layout: fixed */
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 0.9em; word-wrap: break-word; } /* Added word-wrap */
        th { background-color: #f2f2f2; font-weight: bold; text-transform: uppercase; }
        .overall-result { margin-top: 30px; border: 1px solid #ddd; padding: 15px; background-color: #e9f7ef; border-radius: 5px; }
        .overall-result h3 { margin-top: 0; color: #28a745; font-size: 1.1em; }
        .overall-result table { margin-top: 10px; }
        .overall-status { font-weight: bold; padding: 3px 8px; border-radius: 4px; display: inline-block; }
        .pass { color: green; background-color: #e6ffe6; border: 1px solid green; }
        .fail { color: red; background-color: #ffe6e6; border: 1px solid red; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $exam['exam_name'] ?? 'Exam Results' }}</h1>
            <p>Session: {{ $student['session']['name'] ?? 'N/A' }}</p>
            <p>Class: {{ $student['className']['class_name'] ?? 'N/A' }}
                @if($student['section']['name'] ?? false) - Section: {{ $student['section']['name'] }} @endif
                @if($student['group']['name'] ?? false) - Group: {{ $student['group']['name'] }} @endif
            </p>
        </div>

        <div class="student-info">
            <h2>{{ $student['first_name'] ?? '' }} {{ $student['last_name'] ?? '' }}</h2>
            <p><strong>Roll No:</strong> {{ $student['roll_number'] ?? 'N/A' }}</p>
            <p><strong>Admission No:</strong> {{ $student['admission_number'] ?? 'N/A' }}</p>
        </div>

        <h3>Subject-wise Marks</h3>
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Marks Obtained</th>
                    <th>Total Marks</th>
                    <th>Percentage (%)</th>
                    <th>Grade</th>
                    <th>GP</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subjectDetails as $subject)
                <tr>
                    <td>{{ $subject['subject_name'] }}</td>
                    <td>{{ $subject['marks_obtained'] ?? '-' }}</td>
                    <td>{{ $subject['total_marks'] ?? '-' }}</td>
                    <td>{{ $subject['percentage'] ? number_format($subject['percentage'], 2) : '-' }}</td>
                    <td>{{ $subject['letter_grade'] ?? '-' }}</td>
                    <td>{{ $subject['grade_point'] ? number_format($subject['grade_point'], 2) : '-' }}</td>
                    <td><span class="overall-status {{ strtolower($subject['pass_status']) }}">{{ $subject['pass_status'] ?? '-' }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="overall-result">
            <h3>Overall Result</h3>
            <table>
                <tbody>
                    <tr>
                        <th>Total Obtained Marks</th>
                        <td>{{ $overallTotalObtained ?? 0 }} / {{ $overallTotalPossible ?? 0 }}</td>
                    </tr>
                    <tr>
                        <th>Overall Percentage</th>
                        <td>{{ $overallPercentage ? number_format($overallPercentage, 2) : 0 }}%</td>
                    </tr>
                    <tr>
                        <th>Overall GPA</th>
                        <td>{{ $overallGPA ? number_format($overallGPA, 2) : 0 }}</td>
                    </tr>
                    <tr>
                        <th>Overall Letter Grade</th>
                        <td>{{ $overallLetterGrade ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Overall Status</th>
                        <td><span class="overall-status {{ strtolower($overallStatus) }}">{{ $overallStatus ?? 'N/A' }}</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>