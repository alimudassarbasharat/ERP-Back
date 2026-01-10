<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Challan - {{ $challan->challan_no }}</title>
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
        .voucher-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border: 2px solid {{ $config['primary_color'] ?? '#2563eb' }};
        }
        .header {
            border-bottom: 3px solid {{ $config['primary_color'] ?? '#2563eb' }};
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .school-info {
            text-align: center;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: {{ $config['primary_color'] ?? '#2563eb' }};
            margin-bottom: 10px;
        }
        .school-address {
            color: #666;
            font-size: 11px;
        }
        .voucher-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            color: {{ $config['primary_color'] ?? '#2563eb' }};
            text-transform: uppercase;
        }
        .challan-number {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            background: {{ $config['primary_color'] ?? '#2563eb' }};
            color: #fff;
            padding: 10px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .voucher-details {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .voucher-details-row {
            display: table-row;
        }
        .voucher-details-cell {
            display: table-cell;
            padding: 8px;
            width: 50%;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
        .student-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid {{ $config['primary_color'] ?? '#2563eb' }};
        }
        .amount-box {
            text-align: center;
            background: #f0f9ff;
            border: 2px dashed {{ $config['primary_color'] ?? '#2563eb' }};
            padding: 20px;
            margin: 30px 0;
            border-radius: 5px;
        }
        .amount-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .amount-value {
            font-size: 32px;
            font-weight: bold;
            color: {{ $config['primary_color'] ?? '#2563eb' }};
        }
        .payment-instructions {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .payment-instructions h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        .payment-instructions ul {
            margin-left: 20px;
            color: #856404;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 10px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 11px;
        }
        .status-unpaid {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
    </style>
</head>
<body>
    <div class="voucher-container">
        <div class="header">
            <div class="school-info">
                <div class="school-name">{{ $school->name ?? 'School Name' }}</div>
                <div class="school-address">{{ $config['school_address'] ?? 'School Address' }}</div>
            </div>
        </div>

        <div class="voucher-title">Fee Payment Challan</div>

        <div class="challan-number">
            Challan No: {{ $challan->challan_no }}
        </div>

        <div class="voucher-details">
            <div class="voucher-details-row">
                <div class="voucher-details-cell">
                    <span class="label">Issue Date:</span> {{ $challan->generated_at->format('d M Y') }}
                </div>
                <div class="voucher-details-cell text-right">
                    <span class="label">Due Date:</span> {{ $challan->due_date->format('d M Y') }}
                </div>
            </div>
            <div class="voucher-details-row">
                <div class="voucher-details-cell">
                    <span class="label">Session:</span> {{ $session->name ?? 'N/A' }}
                </div>
                <div class="voucher-details-cell text-right">
                    <span class="label">Status:</span>
                    <span class="status-badge status-{{ $challan->status }}">
                        {{ strtoupper($challan->status) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="student-info">
            <div><strong>Student Name:</strong> {{ $student->name ?? ($student->first_name . ' ' . $student->last_name) }}</div>
            <div><strong>Admission No:</strong> {{ $student->admission_no ?? $student->admission_number ?? 'N/A' }}</div>
            <div><strong>Class:</strong> {{ $student->currentClass->name ?? 'N/A' }}</div>
            @if($student->father_name)
            <div><strong>Father Name:</strong> {{ $student->father_name }}</div>
            @endif
        </div>

        <div class="amount-box">
            <div class="amount-label">Amount Payable</div>
            <div class="amount-value">Rs. {{ number_format($challan->amount, 2) }}</div>
        </div>

        <div class="payment-instructions">
            <h4>Payment Instructions:</h4>
            <ul>
                <li>Please pay the amount before the due date to avoid late fees.</li>
                <li>Keep this challan safe for your records.</li>
                <li>Payment can be made via bank transfer, cash, or online payment.</li>
                <li>For any queries, contact the school office.</li>
            </ul>
        </div>

        <div class="footer">
            <p>This is a computer-generated challan. No signature required.</p>
            <p>{{ $config['footer_text'] ?? 'Thank you for your payment.' }}</p>
            <p style="margin-top: 10px;">Generated on: {{ now()->format('d M Y, h:i A') }}</p>
        </div>
    </div>
</body>
</html>
