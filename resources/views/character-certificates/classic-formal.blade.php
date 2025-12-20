<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Character Certificate - Classic Formal</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Times+New+Roman:wght@400;700&display=swap');
        
        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 40px;
            background: #f8f9fa;
        }
        
        .certificate {
            background: white;
            max-width: 800px;
            margin: 0 auto;
            padding: 60px;
            border: 3px solid #8b4513;
            border-radius: 0;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #8b4513;
            padding-bottom: 30px;
            margin-bottom: 40px;
        }
        
        .school-name {
            font-size: 32px;
            font-weight: bold;
            color: #8b4513;
            margin-bottom: 10px;
        }
        
        .certificate-title {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin: 30px 0;
            text-decoration: underline;
        }
        
        .content {
            text-align: justify;
            line-height: 2;
            font-size: 16px;
            color: #2c3e50;
        }
        
        .student-name {
            font-size: 20px;
            font-weight: bold;
            color: #c0392b;
            text-decoration: underline;
            margin: 20px 0;
            text-align: center;
        }
        
        .details {
            margin: 30px 0;
            text-align: center;
        }
        
        .footer {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature {
            text-align: center;
            width: 200px;
        }
        
        .signature-line {
            border-top: 2px solid #2c3e50;
            height: 50px;
            margin-bottom: 10px;
        }
        
        .date {
            margin-top: 40px;
            text-align: right;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">
            <div class="school-name">{{ config('app.school_name', 'Excellence Academy') }}</div>
            <p>{{ config('app.school_address', 'Education Street, Knowledge City') }}</p>
            <div class="certificate-title">CHARACTER CERTIFICATE</div>
        </div>
        
        <div class="content">
            <p>This is to certify that <strong>{{ $student->first_name }} {{ $student->last_name }}</strong>, son/daughter of <strong>{{ $student->father_name ?? 'N/A' }}</strong>, has been a student of this institution.</p>
            
            <div class="student-name">{{ $student->first_name }} {{ $student->last_name }}</div>
            
            <div class="details">
                <p><strong>Roll Number:</strong> {{ $student->roll_number }}</p>
                <p><strong>Class:</strong> {{ $student->class->name ?? 'N/A' }}</p>
                <p><strong>Session:</strong> {{ $student->session ?? '2024-25' }}</p>
            </div>
            
            <p>During his/her stay in this institution, he/she has shown good moral character, discipline, and conduct. He/She has been found to be of good character and is hereby recommended for any future endeavors.</p>
            
            <p>This certificate is issued on his/her request for official purposes.</p>
        </div>
        
        <div class="date">
            <p><strong>Date:</strong> {{ date('d-m-Y') }}</p>
        </div>
        
        <div class="footer">
            <div class="signature">
                <div class="signature-line"></div>
                <p><strong>Class Teacher</strong></p>
            </div>
            <div class="signature">
                <div class="signature-line"></div>
                <p><strong>Principal</strong></p>
            </div>
        </div>
    </div>
</body>
</html> 