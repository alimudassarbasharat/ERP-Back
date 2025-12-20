@php
// Variables expected:
// $profile_image, $employee_name, $designation, $employee_id, $department, $designation_full, $phone, $email, $join_date, $expire_date, $company_logo, $company_name, $company_tagline, $qr_code
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - Style 3</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #eaf3fa;
        }
        .idcard3-container {
            width: 340px;
            height: 540px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.12);
            overflow: hidden;
            font-family: 'Montserrat', Arial, sans-serif;
            margin: 24px auto;
            position: relative;
            border: 2px solid #e0e7ef;
        }
        .idcard3-header {
            background: #2196f3;
            padding: 0;
            height: 120px;
            position: relative;
        }
        .idcard3-logo {
            position: absolute;
            top: 18px;
            left: 18px;
            width: 48px;
            height: 48px;
            object-fit: contain;
        }
        .idcard3-photo {
            position: absolute;
            top: 32px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 4px solid #fff;
            object-fit: cover;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
        }
        .idcard3-body {
            padding: 120px 24px 24px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .idcard3-name {
            color: #2196f3;
            font-size: 1.3rem;
            font-weight: 700;
            margin-top: 12px;
            margin-bottom: 2px;
            text-align: center;
        }
        .idcard3-designation {
            color: #23232b;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 10px;
            text-align: center;
        }
        .idcard3-info {
            width: 100%;
            margin-bottom: 12px;
        }
        .idcard3-info-item {
            color: #23232b;
            font-size: 0.98rem;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
        }
        .idcard3-info-label {
            font-weight: 600;
            color: #2196f3;
        }
        .idcard3-qr {
            width: 70px;
            height: 70px;
            margin: 10px auto 0 auto;
            display: block;
            object-fit: contain;
        }
        .idcard3-footer {
            width: 100%;
            background: #fff;
            padding: 8px 0 0 0;
            text-align: center;
        }
        .idcard3-footer-text {
            color: #23232b;
            font-size: 0.95rem;
            font-weight: 500;
        }
        .idcard3-signature {
            margin-top: 18px;
            text-align: right;
            width: 100%;
            font-size: 0.95rem;
            color: #23232b;
        }
        .idcard3-dates {
            margin-top: 10px;
            font-size: 0.95rem;
            color: #2196f3;
            width: 100%;
            display: flex;
            justify-content: space-between;
        }
        @media print {
            body {
                background: #fff;
            }
            .idcard3-container {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="idcard3-container">
        <div class="idcard3-header">
            <img src="{{ $company_logo ?? 'https://via.placeholder.com/48x48?text=Logo' }}" alt="Logo" class="idcard3-logo">
            <img src="{{ $profile_image ?? 'https://via.placeholder.com/80x80?text=Photo' }}" alt="Profile" class="idcard3-photo">
        </div>
        <div class="idcard3-body">
            <div class="idcard3-name">{{ $employee_name ?? 'JOHN SMITH' }}</div>
            <div class="idcard3-designation">{{ $designation ?? 'Graphic Designer' }}</div>
            <div class="idcard3-info">
                <div class="idcard3-info-item"><span class="idcard3-info-label">ID No:</span> <span>{{ $employee_id ?? '01234' }}</span></div>
                <div class="idcard3-info-item"><span class="idcard3-info-label">Dept:</span> <span>{{ $department ?? 'Graphic Team' }}</span></div>
                <div class="idcard3-info-item"><span class="idcard3-info-label">Deg Sr:</span> <span>{{ $designation_full ?? 'Executive' }}</span></div>
                <div class="idcard3-info-item"><span class="idcard3-info-label">Phone:</span> <span>{{ $phone ?? '01234567890' }}</span></div>
                <div class="idcard3-info-item"><span class="idcard3-info-label">Email:</span> <span>{{ $email ?? 'info@brand.com' }}</span></div>
            </div>
            <img src="{{ $qr_code ?? 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=01234' }}" alt="QR Code" class="idcard3-qr">
            <div class="idcard3-signature">SIGNATURE<br>Your sincerely</div>
            <div class="idcard3-dates">
                <span>JOIN DATE: {{ $join_date ?? '20-10-2023' }}</span>
                <span>EXPIRE DATE: {{ $expire_date ?? '22-12-2024' }}</span>
            </div>
        </div>
        <div class="idcard3-footer">
            <span class="idcard3-footer-text">www.brandurl.com</span>
        </div>
    </div>
</body>
</html> 