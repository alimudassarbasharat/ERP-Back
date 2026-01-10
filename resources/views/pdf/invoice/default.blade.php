<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Invoice - {{ $invoice->id }}</title>
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
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
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
        .invoice-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            color: {{ $config['primary_color'] ?? '#2563eb' }};
        }
        .invoice-details {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .invoice-details-row {
            display: table-row;
        }
        .invoice-details-cell {
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
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background: {{ $config['primary_color'] ?? '#2563eb' }};
            color: #fff;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
        }
        .items-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            margin-top: 20px;
            margin-left: auto;
            width: 300px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        .totals-row.total {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid {{ $config['primary_color'] ?? '#2563eb' }};
            border-bottom: 2px solid {{ $config['primary_color'] ?? '#2563eb' }};
            padding: 12px 0;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 10px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="school-info">
                <div class="school-name">{{ $school->name ?? 'School Name' }}</div>
                <div class="school-address">{{ $config['school_address'] ?? 'School Address' }}</div>
            </div>
        </div>

        <div class="invoice-title">FEE INVOICE</div>

        <div class="invoice-details">
            <div class="invoice-details-row">
                <div class="invoice-details-cell">
                    <span class="label">Invoice #:</span> INV-{{ str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}
                </div>
                <div class="invoice-details-cell text-right">
                    <span class="label">Date:</span> {{ $invoice->generated_at->format('d M Y') }}
                </div>
            </div>
            <div class="invoice-details-row">
                <div class="invoice-details-cell">
                    <span class="label">Billing Month:</span> {{ $invoice->billing_month->format('F Y') }}
                </div>
                <div class="invoice-details-cell text-right">
                    <span class="label">Due Date:</span> {{ $invoice->due_date->format('d M Y') }}
                </div>
            </div>
            <div class="invoice-details-row">
                <div class="invoice-details-cell">
                    <span class="label">Session:</span> {{ $session->name ?? 'N/A' }}
                </div>
                <div class="invoice-details-cell text-right">
                    <span class="label">Status:</span> {{ strtoupper($invoice->status) }}
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

        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fee Head</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->fee_head_name }}</td>
                    <td class="text-right">Rs. {{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <span>Subtotal:</span>
                <span>Rs. {{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            @if($invoice->discount_total > 0)
            <div class="totals-row">
                <span>Discount:</span>
                <span>- Rs. {{ number_format($invoice->discount_total, 2) }}</span>
            </div>
            @endif
            <div class="totals-row total">
                <span>Total Amount:</span>
                <span>Rs. {{ number_format($invoice->total_amount, 2) }}</span>
            </div>
        </div>

        <div class="footer">
            <p>This is a computer-generated invoice. No signature required.</p>
            <p>{{ $config['footer_text'] ?? 'Thank you for your payment.' }}</p>
        </div>
    </div>
</body>
</html>
