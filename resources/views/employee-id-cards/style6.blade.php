@php
// Variables expected:
// $profile_image, $employee_name, $designation, $employee_id, $phone, $blood_group, $email, $barcode, $company_logo, $company_name
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - Style 6 (Front)</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        .idcard6-container {
            width: 340px;
            height: 540px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.12);
            overflow: hidden;
            font-family: 'Montserrat', Arial, sans-serif;
            margin: 24px auto;
            position: relative;
            border: 2px solid #d0e2f7;
        }
        .idcard6-header {
            background: linear-gradient(120deg, #1e90d6 60%, #fff 100%);
            padding: 0;
            height: 120px;
            position: relative;
        }
        .idcard6-logo {
            position: absolute;
            top: 16px;
            left: 16px;
            width: 48px;
            height: 48px;
            object-fit: contain;
        }
        .idcard6-photo {
            position: absolute;
            top: 32px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 80px;
            border-radius: 8px;
            border: 4px solid #fff;
            object-fit: cover;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
        }
        .idcard6-body {
            padding: 120px 24px 24px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .idcard6-name {
            color: #1e90d6;
            font-size: 1.3rem;
            font-weight: 700;
            margin-top: 12px;
            margin-bottom: 2px;
            text-align: center;
        }
        .idcard6-designation {
            color: #23232b;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 10px;
            text-align: center;
        }
        .idcard6-info {
            width: 100%;
            margin-bottom: 12px;
        }
        .idcard6-info-item {
            color: #23232b;
            font-size: 0.98rem;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
        }
        .idcard6-info-label {
            font-weight: 600;
            color: #1e90d6;
        }
        .idcard6-barcode {
            width: 90%;
            height: 38px;
            margin: 0 auto 0 auto;
            display: block;
            object-fit: contain;
        }
        .idcard6-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #fff;
            padding: 8px 0 8px 0;
            text-align: center;
            border-top: 1.5px solid #1e90d6;
        }
        .idcard6-footer-text {
            color: #1e90d6;
            font-size: 0.95rem;
            font-weight: 500;
        }
        @media print {
            body {
                background: #fff;
            }
            .idcard6-container {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="idcard6-container">
        <div class="idcard6-header">
            <img src="{{ $company_logo ?? 'https://via.placeholder.com/48x48?text=Logo' }}" alt="Logo" class="idcard6-logo">
            <img src="{{ $profile_image ?? 'https://via.placeholder.com/80x80?text=Photo' }}" alt="Profile" class="idcard6-photo">
        </div>
        <div class="idcard6-body">
            <div class="idcard6-name">{{ $employee_name ?? 'ADI BARBU' }}</div>
            <div class="idcard6-designation">{{ $designation ?? 'Graphic Designer' }}</div>
            <div class="idcard6-info">
                <div class="idcard6-info-item"><span class="idcard6-info-label">ID No :</span> <span>{{ $employee_id ?? '0000012345678910' }}</span></div>
                <div class="idcard6-info-item"><span class="idcard6-info-label">Phone :</span> <span>{{ $phone ?? '+880 1931 034992' }}</span></div>
                <div class="idcard6-info-item"><span class="idcard6-info-label">Blood :</span> <span>{{ $blood_group ?? 'B+' }}</span></div>
                <div class="idcard6-info-item"><span class="idcard6-info-label">E-mail :</span> <span>{{ $email ?? 'example@gmail.com' }}</span></div>
            </div>
            <img src="https://barcode.tec-it.com/barcode.ashx?data={{ $employee_id ?? '0000012345678910' }}&code=Code128&translate-esc=true" alt="Barcode" class="idcard6-barcode">
        </div>
        <div class="idcard6-footer">
            <span class="idcard6-footer-text">www.graphicsfamily.com</span>
        </div>
    </div>
</body>
</html> 