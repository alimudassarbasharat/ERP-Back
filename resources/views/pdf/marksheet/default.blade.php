<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marksheet - {{ $result->student->name ?? 'Student' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }
        .marksheet-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
        }
        .header {
            border-bottom: 3px solid {{ $config['primary_color'] ?? '#2563eb' }};
            padding-bottom: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: {{ $config['primary_color'] ?? '#2563eb' }};
            margin-bottom: 10px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .student-info {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-row {
            display: table-row;
        }
        .info-cell {
            display: table-cell;
            padding: 8px;
            width: 50%;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
        .marks-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .marks-table th {
            background: {{ $config['primary_color'] ?? '#2563eb' }};
            color: #fff;
            padding: 12px;
            text-align: left;
        }
        .marks-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
        }
        .marks-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .summary-box {
            background: #f0f9ff;
            border: 2px solid {{ $config['primary_color'] ?? '#2563eb' }};
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        .summary-row.total {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid {{ $config['primary_color'] ?? '#2563eb' }};
            border-bottom: 2px solid {{ $config['primary_color'] ?? '#2563eb' }};
            padding: 12px 0;
            margin-top: 10px;
        }
        .grade-box {
            text-align: center;
            background: #fff3cd;
            border: 2px dashed #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .grade-value {
            font-size: 32px;
            font-weight: bold;
            color: {{ $config['primary_color'] ?? '#2563eb' }};
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 10px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="marksheet-container">
        <div class="header">
            <div class="school-name">{{ $school->name ?? 'School Name' }}</div>
            <div style="color: #666; font-size: 11px;">{{ $config['school_address'] ?? 'School Address' }}</div>
        </div>

        <div class="title">EXAMINATION RESULT</div>

        <div class="student-info">
            <div class="info-row">
                <div class="info-cell">
                    <span class="label">Student Name:</span> {{ $student->name ?? ($student->first_name . ' ' . $student->last_name) }}
                </div>
                <div class="info-cell">
                    <span class="label">Admission No:</span> {{ $student->admission_no ?? $student->admission_number ?? 'N/A' }}
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell">
                    <span class="label">Exam:</span> {{ $exam->name ?? 'N/A' }}
                </div>
                <div class="info-cell">
                    <span class="label">Session:</span> {{ $exam->session->name ?? 'N/A' }}
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell">
                    <span class="label">Class:</span> {{ $student->currentClass->name ?? 'N/A' }}
                </div>
                <div class="info-cell">
                    <span class="label">Rank:</span> {{ $result->rank_in_class ? '#' . $result->rank_in_class : 'N/A' }}
                </div>
            </div>
        </div>

        <table class="marks-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Subject</th>
                    <th class="text-right">Marks Obtained</th>
                    <th class="text-right">Total Marks</th>
                    <th class="text-right">Percentage</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $snapshot = $result->result_snapshot_json ?? [];
                    $marks = $snapshot['marks'] ?? [];
                @endphp
                @foreach($marks as $index => $mark)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $mark['subject_name'] ?? 'N/A' }}</td>
                    <td class="text-right">
                        @if($mark['is_absent'] ?? false)
                            <span style="color: red;">Absent</span>
                        @else
                            {{ number_format($mark['marks_obtained'] ?? 0, 2) }}
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($mark['total_marks'] ?? 0, 2) }}</td>
                    <td class="text-right">
                        @if(isset($mark['marks_obtained']) && isset($mark['total_marks']) && $mark['total_marks'] > 0)
                            {{ number_format(($mark['marks_obtained'] / $mark['total_marks']) * 100, 2) }}%
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary-box">
            <div class="summary-row">
                <span>Total Marks Obtained:</span>
                <span>{{ number_format($result->total_obtained, 2) }}</span>
            </div>
            <div class="summary-row">
                <span>Total Marks:</span>
                <span>{{ number_format($result->total_marks, 2) }}</span>
            </div>
            <div class="summary-row total">
                <span>Percentage:</span>
                <span>{{ number_format($result->percentage, 2) }}%</span>
            </div>
        </div>

        <div class="grade-box">
            <div style="font-size: 14px; color: #666; margin-bottom: 10px;">Grade</div>
            <div class="grade-value">{{ $result->grade ?? 'N/A' }}</div>
        </div>

        <div class="footer">
            <p>This is a computer-generated marksheet. No signature required.</p>
            <p>Generated on: {{ now()->format('d M Y, h:i A') }}</p>
        </div>
    </div>
</body>
</html>
