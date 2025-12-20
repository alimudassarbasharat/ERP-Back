<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report Card</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 20px;
            margin-bottom: 20px;
        }
        .student-info {
            margin-bottom: 30px;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        .info-label {
            width: 150px;
            font-weight: bold;
        }
        .marks-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .marks-table th, .marks-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .marks-table th {
            background-color: #f5f5f5;
        }
        .summary {
            margin-bottom: 30px;
        }
        .attendance {
            margin-bottom: 30px;
        }
        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">School Name</div>
        <div class="report-title">Academic Report Card</div>
    </div>

    <div class="student-info">
        <div class="info-row">
            <div class="info-label">Student Name:</div>
            <div>{{ $student->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Student ID:</div>
            <div>{{ $student->student_id }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Class:</div>
            <div>{{ $student->class->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Section:</div>
            <div>{{ $student->section->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Exam:</div>
            <div>{{ $result->exam->name }}</div>
        </div>
    </div>

    <table class="marks-table">
        <thead>
            <tr>
                <th>Subject</th>
                <th>Marks Obtained</th>
                <th>Maximum Marks</th>
                <th>Percentage</th>
                <th>Grade</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($result->subjectMarks as $mark)
            <tr>
                <td>{{ $mark->subject->name }}</td>
                <td>{{ $mark->mark_obtained }}</td>
                <td>{{ $mark->max_marks }}</td>
                <td>{{ number_format(($mark->mark_obtained / $mark->max_marks) * 100, 2) }}%</td>
                <td>{{ $mark->grade }}</td>
                <td>{{ $mark->remarks }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <h3>Overall Performance</h3>
        <div class="info-row">
            <div class="info-label">Total Marks:</div>
            <div>{{ $result->total_marks }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Marks Obtained:</div>
            <div>{{ $result->total_mark_obtains }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Percentage:</div>
            <div>{{ $result->percentage }}%</div>
        </div>
        <div class="info-row">
            <div class="info-label">Grade:</div>
            <div>{{ $result->grade }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Position:</div>
            <div>{{ $result->position }}</div>
        </div>
    </div>

    <div class="attendance">
        <h3>Attendance Record</h3>
        <div class="info-row">
            <div class="info-label">Attendance Percentage:</div>
            <div>{{ $attendance_percentage }}%</div>
        </div>
    </div>

    <div class="footer">
        <div class="signature">
            <div class="signature-line"></div>
            <div>Class Teacher</div>
        </div>
        <div class="signature">
            <div class="signature-line"></div>
            <div>Principal</div>
        </div>
    </div>
</body>
</html> 