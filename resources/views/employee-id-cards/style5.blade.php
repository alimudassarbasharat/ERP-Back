@php
// Variables expected:
// $profile_image, $employee_name, $designation, $employee_id, $email, $barcode, $company_logo, $company_name
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - Style 5</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        .idcard5-container {
            width: 340px;
            height: 540px;
            background: #eaf3fa;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.12);
            overflow: hidden;
            font-family: 'Montserrat', Arial, sans-serif;
            margin: 24px auto;
            position: relative;
            border: 2px solid #d0e2f7;
        }
        .idcard5-header {
            background: linear-gradient(120deg, #b2d7f7 60%, #eaf3fa 100%);
            padding: 0;
            height: 120px;
            position: relative;
        }
        .idcard5-logo {
            position: absolute;
            top: 16px;
            left: 16px;
            width: 48px;
            height: 48px;
            object-fit: contain;
        }
        .idcard5-photo {
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
        .idcard5-body {
            padding: 120px 24px 24px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .idcard5-name {
            color: #2a4d7a;
            font-size: 1.3rem;
            font-weight: 700;
            margin-top: 12px;
            margin-bottom: 2px;
            text-align: center;
        }
        .idcard5-designation {
            color: #e573a7;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 16px;
            text-align: center;
        }
        .idcard5-info {
            width: 100%;
            margin-bottom: 18px;
        }
        .idcard5-info-item {
            color: #23232b;
            font-size: 0.98rem;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
        }
        .idcard5-info-label {
            font-weight: 600;
            color: #2a4d7a;
        }
        .idcard5-barcode {
            width: 90%;
            height: 38px;
            margin: 0 auto 0 auto;
            display: block;
            object-fit: contain;
        }
        .idcard5-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #eaf3fa;
            padding: 8px 0 8px 0;
            text-align: center;
            border-top: 1.5px solid #b2d7f7;
        }
        .idcard5-footer-text {
            color: #2a4d7a;
            font-size: 0.95rem;
            font-weight: 500;
        }
        @media print {
            body {
                background: #fff;
            }
            .idcard5-container {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="idcard5-container">
        <div class="idcard5-header">
            <img src="{{ $company_logo ?? 'https://via.placeholder.com/48x48?text=Logo' }}" alt="Logo" class="idcard5-logo">
            <img src="{{ $profile_image ?? 'https://via.placeholder.com/80x80?text=Photo' }}" alt="Profile" class="idcard5-photo">
        </div>
        <div class="idcard5-body">
            <div class="idcard5-name">{{ $employee_name ?? 'ANGEL ROSE' }}</div>
            <div class="idcard5-designation">{{ $designation ?? 'Graphic Designer' }}</div>
            <div class="idcard5-info">
                <div class="idcard5-info-item"><span class="idcard5-info-label">ID :</span> <span>{{ $employee_id ?? '102030405060708090100' }}</span></div>
                <div class="idcard5-info-item"><span class="idcard5-info-label">Email :</span> <span>{{ $email ?? 'example@gmail.com' }}</span></div>
            </div>
            <img src="https://barcode.tec-it.com/barcode.ashx?data={{ $employee_id ?? '102030405060708090100' }}&code=Code128&translate-esc=true" alt="Barcode" class="idcard5-barcode">
        </div>
        <div class="idcard5-footer">
            <span class="idcard5-footer-text">PZN-4908802</span>
        </div>
    </div>
</body>
</html> 