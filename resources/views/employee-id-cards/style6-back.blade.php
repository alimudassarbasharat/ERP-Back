@php
// Variables expected:
// $company_logo, $company_name, $terms, $info_email, $info_website, $info_address, $info_phone, $qr_code
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - Style 6 (Back)</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        .idcard6b-container {
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
        .idcard6b-header {
            background: linear-gradient(120deg, #1e90d6 60%, #fff 100%);
            padding: 0;
            height: 120px;
            position: relative;
        }
        .idcard6b-logo {
            position: absolute;
            top: 16px;
            left: 16px;
            width: 48px;
            height: 48px;
            object-fit: contain;
        }
        .idcard6b-qr {
            position: absolute;
            top: 32px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 80px;
            object-fit: contain;
            background: #fff;
            border-radius: 8px;
            border: 4px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
        }
        .idcard6b-body {
            padding: 120px 24px 24px 24px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .idcard6b-title {
            color: #1e90d6;
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .idcard6b-terms {
            color: #23232b;
            font-size: 0.98rem;
            margin-bottom: 12px;
        }
        .idcard6b-info {
            color: #23232b;
            font-size: 0.98rem;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
        }
        .idcard6b-info-icon {
            margin-right: 8px;
            color: #1e90d6;
        }
        .idcard6b-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #fff;
            padding: 8px 0 8px 0;
            text-align: center;
            border-top: 1.5px solid #1e90d6;
        }
        .idcard6b-footer-text {
            color: #1e90d6;
            font-size: 0.95rem;
            font-weight: 500;
        }
        @media print {
            body {
                background: #fff;
            }
            .idcard6b-container {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="idcard6b-container">
        <div class="idcard6b-header">
            <img src="{{ $company_logo ?? 'https://via.placeholder.com/48x48?text=Logo' }}" alt="Logo" class="idcard6b-logo">
            <img src="{{ $qr_code ?? 'https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=www.graphicsfamily.com' }}" alt="QR Code" class="idcard6b-qr">
        </div>
        <div class="idcard6b-body">
            <div class="idcard6b-title">Terms And Conditions</div>
            <div class="idcard6b-terms">
                {!! $terms ?? '<ul style="margin:0 0 0 18px;padding:0;"><li>Lorem ipsum dolor sit amet, consectetur adipisicing elit sed</li><li>Lorem ipsum dolor sit amet, consectetur adipisicing elit sed</li><li>Lorem ipsum dolor sit amet, consectetur adipisicing elit sed</li></ul>' !!}
            </div>
            <div class="idcard6b-title" style="margin-top:10px;">Information</div>
            <div class="idcard6b-info"><span class="idcard6b-info-icon">&#9993;</span> {{ $info_email ?? 'contact@graphicsfamily.com' }}</div>
            <div class="idcard6b-info"><span class="idcard6b-info-icon">&#128279;</span> {{ $info_website ?? 'www.graphicsfamily.com' }}</div>
            <div class="idcard6b-info"><span class="idcard6b-info-icon">&#128205;</span> {{ $info_address ?? '245 North 13th Street, Office 103, Los Angeles' }}</div>
            <div class="idcard6b-info"><span class="idcard6b-info-icon">&#128222;</span> {{ $info_phone ?? '+880 1931 034992' }}</div>
        </div>
        <div class="idcard6b-footer">
            <span class="idcard6b-footer-text">www.graphicsfamily.com</span>
        </div>
    </div>
</body>
</html> 