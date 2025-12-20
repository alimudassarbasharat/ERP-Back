@php
// Variables expected:
// $profile_image, $employee_name, $designation, $employee_id, $dob, $phone, $company_logo, $company_name, $company_tagline
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - Style 1</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        .idcard-container {
            width: 350px;
            height: 550px;
            background: #23232b;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.12);
            overflow: hidden;
            font-family: 'Montserrat', Arial, sans-serif;
            margin: 24px auto;
            position: relative;
        }
        .idcard-header {
            position: relative;
            height: 210px;
            background: linear-gradient(135deg, #e53935 60%, #23232b 100%);
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
            overflow: hidden;
        }
        .header-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            justify-content: space-between;
            padding: 28px 28px 0 28px;
        }
        .company-logo {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }
        .header-text {
            text-align: right;
            margin-left: auto;
        }
        .company-name {
            color: #fff;
            font-size: 1.18rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .company-tagline {
            color: #fff;
            font-size: 0.92rem;
            font-weight: 400;
            letter-spacing: 0.2px;
        }
        .header-wave {
            position: absolute;
            left: 0;
            bottom: -1px;
            width: 100%;
            z-index: 1;
        }
        .profile-pic-wrapper {
            position: absolute;
            left: 50%;
            bottom: -60px;
            transform: translateX(-50%);
            width: 120px;
            height: 120px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 12px rgba(229,57,53,0.18);
            border: 7px solid #fff;
            z-index: 3;
        }
        .profile-pic {
            width: 106px;
            height: 106px;
            border-radius: 50%;
            object-fit: cover;
        }
        .idcard-body {
            background: #fff;
            border-radius: 0 0 18px 18px;
            padding: 80px 24px 24px 24px;
            min-height: 320px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .employee-name {
            color: #e53935;
            font-size: 1.32rem;
            font-weight: 700;
            margin-top: 8px;
            margin-bottom: 2px;
            text-align: center;
            letter-spacing: 0.5px;
        }
        .designation {
            color: #23232b;
            font-size: 1.05rem;
            font-weight: 500;
            margin-bottom: 18px;
            text-align: center;
            letter-spacing: 0.2px;
        }
        .info-list {
            width: 100%;
            margin-bottom: 18px;
        }
        .info-item {
            color: #23232b;
            font-size: 1.01rem;
            margin-bottom: 7px;
            display: flex;
            justify-content: flex-start;
            gap: 10px;
        }
        .info-label {
            font-weight: 600;
            color: #e53935;
            min-width: 60px;
            display: inline-block;
        }
        .barcode-box {
            width: 200px;
            height: 48px;
            background: #fff;
            border: 1.5px solid #23232b;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 18px auto 0 auto;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .barcode {
            width: 180px;
            height: 38px;
            object-fit: contain;
            background: #fff;
            display: block;
        }
        @media print {
            body {
                background: #fff;
            }
            .idcard-container {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="idcard-container">
        <div class="idcard-header">
            <div class="header-content">
                <img src="{{ $company_logo ?? 'https://via.placeholder.com/48x48?text=Logo' }}" alt="Logo" class="company-logo">
                <div class="header-text">
                    <div class="company-name">{{ $company_name ?? 'BUSINESS' }}</div>
                    <div class="company-tagline">{{ $company_tagline ?? 'TAGLINE HERE' }}</div>
                </div>
            </div>
            <div class="header-wave">
                <svg viewBox="0 0 350 60" width="350" height="60" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 0 Q 60 60 175 40 Q 290 20 350 60 V60 H0 V0Z" fill="#fff"/>
                </svg>
            </div>
            <div class="profile-pic-wrapper">
                <img src="{{ $profile_image ?? 'https://via.placeholder.com/110x110?text=Photo' }}" alt="Profile" class="profile-pic">
            </div>
        </div>
        <div class="idcard-body">
            <div class="employee-name">{{ $employee_name ?? 'MARIA SMITH' }}</div>
            <div class="designation">{{ $designation ?? 'GRAPHIC DESIGNER' }}</div>
            <div class="info-list">
                <div class="info-item"><span class="info-label">ID:</span> <span>{{ $employee_id ?? 'AA-000000' }}</span></div>
                <div class="info-item"><span class="info-label">DOB:</span> <span>{{ $dob ?? '12/12/2000' }}</span></div>
                <div class="info-item"><span class="info-label">Phone:</span> <span>{{ $phone ?? '+00 1234 5678' }}</span></div>
            </div>
            <div class="barcode-box">
                <img src="https://barcode.tec-it.com/barcode.ashx?data={{ $employee_id ?? 'AA-000000' }}&code=Code128&translate-esc=true" alt="Barcode" class="barcode">
            </div>
        </div>
    </div>
</body>
</html> 