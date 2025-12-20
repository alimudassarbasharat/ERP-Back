<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Character Certificate - Modern Gradient</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: linear-gradient(135deg, #667eea, #764ba2); }
        .certificate { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .header { text-align: center; margin-bottom: 30px; }
        .title { font-size: 28px; color: #667eea; font-weight: bold; }
        .content { text-align: center; line-height: 1.8; }
        .student-name { font-size: 24px; font-weight: bold; color: #333; margin: 20px 0; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">
            <h1 class="title">CHARACTER CERTIFICATE</h1>
            <p>Excellence Academy</p>
        </div>
        <div class="content">
            <p>This is to certify that</p>
            <div class="student-name">{{ $student->first_name }} {{ $student->last_name }}</div>
            <p>has been a student of this institution and has maintained excellent character.</p>
            <p>Roll Number: {{ $student->roll_number }}</p>
            <p>Class: {{ $student->class->name ?? 'N/A' }}</p>
            <p>Date: {{ date('d M, Y') }}</p>
        </div>
    </div>
</body>
</html> 