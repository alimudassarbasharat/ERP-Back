@php
// Variables expected:
// $profile_image, $employee_name, $designation, $employee_id, $email, $blood_group, $barcode, $company_logo, $company_name
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - Style 4</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        .idcard4-container {
            width: 340px;
            height: 540px;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.12);
            overflow: hidden;
            font-family: 'Montserrat', Arial, sans-serif;
            margin: 24px auto;
            position: relative;
            border: 2px solid #f2f2f2;
        }
        .idcard4-header {
            background: linear-gradient(120deg, #ff9800 60%, #0a1a3c 100%);
            padding: 0;
            height: 120px;
            position: relative;
        }
        .idcard4-logo {
            position: absolute;
            top: 16px;
            left: 16px;
            width: 48px;
            height: 48px;
            object-fit: contain;
        }
        .idcard4-photo {
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
        .idcard4-body {
            padding: 120px 24px 24px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .idcard4-name {
            color: #0a1a3c;
            font-size: 1.3rem;
            font-weight: 700;
            margin-top: 12px;
            margin-bottom: 2px;
            text-align: center;
        }
        .idcard4-designation {
            color: #ff9800;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 16px;
            text-align: center;
        }
        .idcard4-info {
            width: 100%;
            margin-bottom: 18px;
        }
        .idcard4-info-item {
            color: #23232b;
            font-size: 0.98rem;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
        }
        .idcard4-info-label {
            font-weight: 600;
            color: #0a1a3c;
        }
        .idcard4-barcode {
            width: 90%;
            height: 38px;
            margin: 0 auto 0 auto;
            display: block;
            object-fit: contain;
        }
        .idcard4-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #fff;
            padding: 8px 0 8px 0;
            text-align: center;
            border-top: 1.5px solid #ff9800;
        }
        .idcard4-footer-text {
            color: #0a1a3c;
            font-size: 0.95rem;
            font-weight: 500;
        }
        @media print {
            body {
                background: #fff;
            }
            .idcard4-container {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="idcard4-container">
        <div class="idcard4-header">
            <img src="{{ $company_logo ?? 'https://via.placeholder.com/48x48?text=Logo' }}" alt="Logo" class="idcard4-logo">
            <img src="{{ $profile_image ?? 'https://via.placeholder.com/80x80?text=Photo' }}" alt="Profile" class="idcard4-photo">
        </div>
        <div class="idcard4-body">
            <div class="idcard4-name">{{ $employee_name ?? 'ADI BARBU' }}</div>
            <div class="idcard4-designation">{{ $designation ?? 'Graphic Designer' }}</div>
            <div class="idcard4-info">
                <div class="idcard4-info-item"><span class="idcard4-info-label">Id Nu :</span> <span>{{ $employee_id ?? '10205421055400121' }}</span></div>
                <div class="idcard4-info-item"><span class="idcard4-info-label">Email :</span> <span>{{ $email ?? 'email@gmail.com' }}</span></div>
                <div class="idcard4-info-item"><span class="idcard4-info-label">Blood :</span> <span>{{ $blood_group ?? 'B+' }}</span></div>
            </div>
            <img src="https://barcode.tec-it.com/barcode.ashx?data={{ $employee_id ?? '10205421055400121' }}&code=Code128&translate-esc=true" alt="Barcode" class="idcard4-barcode">
        </div>
        <div class="idcard4-footer">
            <span class="idcard4-footer-text">www.graphicsfamily.com</span>
        </div>
    </div>
</body>
</html> 