<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student ID Card - Modern 3D</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            margin: 0;
            padding: 60px;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .id-card {
            width: 350px;
            height: 220px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            border-radius: 20px;
            padding: 2px;
            position: relative;
            box-shadow: 
                0 20px 40px rgba(0,0,0,0.3),
                0 0 0 1px rgba(255,255,255,0.1),
                inset 0 1px 0 rgba(255,255,255,0.2);
            transform: perspective(1000px) rotateY(-5deg) rotateX(5deg);
        }
        
        .id-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, 
                transparent 30%, 
                rgba(255,255,255,0.1) 50%, 
                transparent 70%);
            border-radius: 20px;
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%) skewX(-15deg); }
            100% { transform: translateX(200%) skewX(-15deg); }
        }
        
        .card-inner {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 18px;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 15px 20px;
            color: white;
            position: relative;
        }
        
        .header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #f093fb, #f5576c, #4facfe, #00f2fe);
        }
        
        .school-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .school-logo {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            backdrop-filter: blur(10px);
        }
        
        .school-details h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .school-details p {
            margin: 2px 0 0 0;
            font-size: 10px;
            opacity: 0.9;
        }
        
        .content {
            padding: 15px 20px;
            display: flex;
            gap: 15px;
            height: calc(100% - 70px);
        }
        
        .photo-section {
            flex-shrink: 0;
        }
        
        .student-photo {
            width: 70px;
            height: 85px;
            background: linear-gradient(135deg, #e2e8f0, #cbd5e0);
            border-radius: 8px;
            border: 2px solid #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #667eea;
            position: relative;
            overflow: hidden;
        }
        
        .student-photo::before {
            content: '👤';
            position: absolute;
        }
        
        .info-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .student-name {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .details {
            space-y: 6px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 11px;
        }
        
        .detail-label {
            color: #718096;
            font-weight: 500;
        }
        
        .detail-value {
            color: #2d3748;
            font-weight: 600;
        }
        
        .id-number {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 600;
            text-align: center;
            margin-top: 8px;
            box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
        }
        
        .validity {
            position: absolute;
            bottom: 8px;
            right: 15px;
            font-size: 9px;
            color: #718096;
            background: rgba(255,255,255,0.8);
            padding: 2px 6px;
            border-radius: 4px;
        }
        
        .hologram {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            background: conic-gradient(from 0deg, #ff006e, #8338ec, #3a86ff, #06ffa5, #ffbe0b, #ff006e);
            border-radius: 50%;
            opacity: 0.6;
            animation: rotate 4s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .back-card {
            margin-top: 40px;
        }
        
        .emergency-info {
            background: #f7fafc;
            padding: 15px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        
        .emergency-title {
            font-size: 12px;
            font-weight: 600;
            color: #e53e3e;
            margin-bottom: 8px;
            text-align: center;
        }
        
        .emergency-text {
            font-size: 9px;
            color: #4a5568;
            text-align: center;
            line-height: 1.4;
        }
        
        .qr-section {
            text-align: center;
            margin-top: 15px;
        }
        
        .qr-code {
            width: 50px;
            height: 50px;
            background: #2d3748;
            border-radius: 8px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="id-card">
        <div class="card-inner">
            <div class="hologram"></div>
            
            <div class="header">
                <div class="school-info">
                    <div class="school-logo">S</div>
                    <div class="school-details">
                        <h3>{{ config('app.school_name', 'Excellence Academy') }}</h3>
                        <p>STUDENT IDENTITY CARD</p>
                    </div>
                </div>
            </div>
            
            <div class="content">
                <div class="photo-section">
                    <div class="student-photo"></div>
                </div>
                
                <div class="info-section">
                    <div>
                        <div class="student-name">{{ $student->first_name }} {{ $student->last_name }}</div>
                        
                        <div class="details">
                            <div class="detail-row">
                                <span class="detail-label">Roll No:</span>
                                <span class="detail-value">{{ $student->roll_number }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Class:</span>
                                <span class="detail-value">{{ $student->class->name ?? 'N/A' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Section:</span>
                                <span class="detail-value">{{ $student->section ?? 'A' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Session:</span>
                                <span class="detail-value">{{ $sessionYear ?? '2024-25' }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="id-number">
                        ID: {{ str_pad($student->id, 6, '0', STR_PAD_LEFT) }}
                    </div>
                </div>
            </div>
            
            <div class="validity">
                Valid till: {{ date('M Y', strtotime('+1 year')) }}
            </div>
        </div>
    </div>
    
    <!-- Back of the card -->
    <div class="id-card back-card">
        <div class="card-inner">
            <div class="content">
                <div class="emergency-info">
                    <div class="emergency-title">EMERGENCY CONTACT</div>
                    <div class="emergency-text">
                        In case of emergency, please contact:<br>
                        School Office: {{ config('app.school_phone', '+1 234 567 8900') }}<br>
                        {{ config('app.school_email', 'info@excellenceacademy.edu') }}
                    </div>
                </div>
                
                <div class="qr-section">
                    <div class="qr-code">📱</div>
                    <div style="font-size: 8px; color: #718096; margin-top: 5px;">
                        Scan for digital verification
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 15px; font-size: 8px; color: #a0aec0;">
                    This card is property of {{ config('app.school_name', 'Excellence Academy') }}<br>
                    If found, please return to school office
                </div>
            </div>
        </div>
    </div>
</body>
</html> 