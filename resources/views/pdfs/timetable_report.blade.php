<!DOCTYPE html>
<html>
<head>
    <title>Class Timetable Report</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        /* Define common styles for the PDF */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .header img {
            max-width: 80px;
            max-height: 80px;
            margin-bottom: 5px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            color: #333;
        }
        .header p {
            margin: 0;
            font-size: 10px;
            color: #555;
        }
        .filters {
            margin-bottom: 15px;
            background-color: #f4f4f4;
            padding: 8px;
            border-radius: 4px;
        }
        .filters p {
            margin: 0;
            line-height: 1.5;
            font-size: 9px;
        }
        .timetable {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .timetable th, .timetable td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: center;
            vertical-align: top;
            font-size: 9px;
        }
        .timetable th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        .day-header {
            background-color: #e9ecef;
            font-weight: bold;
            text-align: left !important;
        }
        .entry-subject {
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
            font-size: 10px;
            display: block;
        }
        .entry-detail {
            font-size: 8px;
            color: #555;
            line-height: 1.2;
        }
    </style>
</head>
<body>

    {{-- School Header --}}
    <div class="header">
        @if (!empty($schoolLogoUrl))
            <img src="{{ $schoolLogoUrl }}" alt="School Logo" style="height: 60px; margin-bottom: 10px;">
        @endif
        <h1>{{ $settings->school_name ?? 'School Timetable Report' }}</h1>
        <p>{{ $settings->address ?? 'Not Available' }}</p>
        <p>{{ $settings->phone_number ?? 'Not Available' }}</p>
    </div>

    {{-- Applied Filters (Optional but helpful for context) --}}
    <div class="filters">
        <p><strong>Report Filters Applied:</strong></p>
        
        @if (data_get($appliedFilters, 'class_name_id'))
            @php
                $selectedClass = $classes->firstWhere('id', data_get($appliedFilters, 'class_name_id'));
            @endphp
            <p>Class: {{ $selectedClass->class_name ?? 'N/A' }}</p>
        @endif

        {{-- Display Selected Section --}}
        @if (data_get($appliedFilters, 'section_id'))
            @php
                $selectedSection = $sections->firstWhere('id', data_get($appliedFilters, 'section_id')); 
            @endphp
            <p>Section: {{ $selectedSection->name ?? 'N/A' }}</p>
        @endif
        
        @if (data_get($appliedFilters, 'teacher_id'))
            <p>Teacher: {{ optional($teachers->firstWhere('id', data_get($appliedFilters, 'teacher_id')))->name ?? 'Filtered' }}</p>
        @endif
        
        @if (data_get($appliedFilters, 'day_of_week'))
            <p>Day: {{ data_get($appliedFilters, 'day_of_week') }}</p>
        @endif
        
        <p>Generated on: {{ date('Y-m-d') }}</p>
    </div>

    {{-- Timetable Table --}}
    <table class="timetable">
        <thead>
            <tr>
                <th>Time Slot</th>
                @foreach ($daysOfWeek as $day)
                    <th>{{ $day }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($timeSlots as $slot)
                <tr>
                    {{-- Time Slot Header (First Column) --}}
                    <td class="day-header" style="text-align:center !important;">
                        {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }} - 
                        {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}
                    </td>
                    
                    {{-- Loop through days --}}
                    @foreach ($daysOfWeek as $day)
                        <td>
                            @php
                                // Find the entry for the current day and time slot
                                $entry = $groupedTimetable->get($day)
                                    ?->firstWhere('class_time_slot_id', $slot->id);
                            @endphp

                            @if ($entry)
                                <span class="entry-subject">{{ $entry->subject->name ?? 'N/A' }}</span>
                                <span class="entry-detail">Class: {{ $entry->className->class_name ?? 'N/A' }} ({{ $entry->section->name ?? 'N/A' }})</span>
                                <span class="entry-detail">Teacher: {{ $entry->teacher->name ?? 'N/A' }}</span>
                                <span class="entry-detail">Room: {{ $entry->room->name ?? 'N/A' }}</span>
                            @else
                                <span class="entry-detail" style="color:#aaa;">---</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($daysOfWeek) + 1 }}" style="text-align: center;">No time slots defined.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>