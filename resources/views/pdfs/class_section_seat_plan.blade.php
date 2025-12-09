<!DOCTYPE html>
<html>
<head>
    <title>Class and Section Seat Plan</title>
    <style>
        /* General Body and Container Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px; /* Slightly larger base font for readability */
            margin: 0;
            padding: 30px;
            background-color: #f4f7f6; /* Light, calming background */
            color: #333;
        }
        
        /* Header Styles */
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 15px 0;
            border-bottom: 4px solid #1abc9c; /* Primary color separator */
            background-color: #ffffff; /* White background for header */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 3px;
            color: #2c3e50;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 0;
            color: #7f8c8d;
            font-size: 14px;
        }
        .header h2 {
            font-size: 20px;
            color: #1abc9c; /* Primary color for main title */
            margin-top: 10px;
            margin-bottom: 0;
            font-weight: 600;
        }
        
        /* Card Container Styles */
        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 25px; /* Increased gap for better separation */
            justify-content: center;
            margin-bottom: 40px; /* Space above the footer */
        }
        
        /* Individual Card Styles */
        .card {
            border: 1px solid #bdc3c7;
            padding: 15px;
            width: 300px; /* Slightly wider card */
            height: 250px; /* Slightly taller card */
            text-align: left; /* Align text left within the card */
            page-break-inside: avoid;
            background-color: #ffffff; /* White background for clean look */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); /* Stronger shadow for depth */
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-3px); /* Slight lift on hover */
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .card-content p {
            margin: 5px 0;
            color: #34495e;
            font-size: 13px;
        }
        
        .card-content strong {
            font-weight: 600;
            color: #2c3e50;
            display: inline-block;
            width: 100px; /* Aligns the data points */
        }
        
        /* Roll Number Area */
        .roll-no-area {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 3px dashed #3498db; /* Blue dashed line separator */
        }
        
        .roll-no-area p {
            margin: 0;
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .roll-no {
            font-weight: 800; /* Extra bold */
            font-size: 32px; /* Large size */
            color: #e74c3c; /* Striking red color */
            letter-spacing: 1px;
            margin-top: 5px;
        }

        /* Footer Styles */
        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 11px;
            color: #7f8c8d;
            border-top: 1px solid #bdc3c7;
            padding-top: 10px;
        }
        .footer a {
            color: #1abc9c;
            text-decoration: none;
            font-weight: 600;
        }

        /* Print Styles */
        @media print {
            body {
                background-color: #fff;
                padding: 0;
                font-size: 12px;
            }
            .header {
                box-shadow: none;
                border-bottom: 2px solid #1abc9c;
                margin-bottom: 15px;
            }
            .card {
                page-break-inside: avoid;
                box-shadow: none;
                border: 1px solid #333; /* Darker border for print clarity */
                width: 280px;
                height: 240px;
                margin-bottom: 15px; /* Add space between cards for print */
            }
            .card-container {
                gap: 15px;
                justify-content: flex-start;
                margin-bottom: 15px; /* Reduce space for print */
            }
            .card-content strong {
                width: 80px;
            }
            .footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                padding: 5px 0;
                margin: 0;
                border-top: 1px solid #ccc;
                background: #fff;
            }
        }
        
        /* Responsive Styles (Small Screens) */
        @media screen and (max-width: 600px) {
            .card {
                width: 90%; /* Full width on small screens */
                height: auto;
                margin: 10px auto;
            }
            .header h1 {
                font-size: 20px;
            }
            .header h2 {
                font-size: 18px;
            }
            .card-container {
                padding: 0 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $setting->school_name ?? 'Your School Name' }}</h1>
        <p>{{ $setting->address ?? 'Talali, Barguna' }}</p>
        <h2>Exam Seat Plan</h2>
    </div>
    <div class="card-container">
        @forelse ($seatAssignments as $assignment)
            <div class="card">
                <div class="card-content">
                    <p><strong>Name:</strong> {{ $assignment['name'] }}</p>
                    <p><strong>Student ID:</strong> {{ $assignment['admission_number'] }}</p>
                    <p><strong>Class:</strong> {{ $class->class_name ?? 'N/A' }} ({{ $section->name ?? 'N/A' }})</p>
                    <p><strong>Group:</strong> {{ $assignment['group_name'] ?? 'N/A' }}</p>
                    <p><strong>Year/Session:</strong> {{ $session->name ?? 'N/A' }}</p>
                    <p><strong>Exam Name:</strong> {{ $examSchedule->exam->exam_name ?? 'N/A' }}</p>
                </div>
                <div class="roll-no-area">
                    <p>Seat Number</p>
                    <div class="roll-no">{{ $assignment['seat_number'] }}</div>
                </div>
            </div>
        @empty
            <p style="text-align: center; color: #e74c3c; font-size: 18px; margin-top: 50px;">
                No students assigned seats for this examination setup.
            </p>
        @endforelse
    </div>

    {{-- Added Footer Section --}}
    <div class="footer">
        <p>
           Generated on: **{{ date('F j, Y') }}**
        </p>
        <p>
            Design and Developed by <a href="https://smithitbd.com" target="_blank">Smith IT</a>
        </p>
    </div>
</body>
</html>