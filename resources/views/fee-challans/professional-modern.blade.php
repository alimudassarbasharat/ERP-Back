<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fee Challan - Professional Modern</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #2d3748;
        }
        
        .challan {
            background: white;
            max-width: 800px;
            margin: 0 auto;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            position: relative;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        .school-info {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        
        .school-name {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .challan-title {
            font-size: 20px;
            font-weight: 500;
            margin-top: 20px;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .student-info {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .info-value {
            font-size: 16px;
            color: #2d3748;
            font-weight: 600;
        }
        
        .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .fee-table th {
            background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        
        .fee-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        
        .fee-table tbody tr:hover {
            background: #f7fafc;
        }
        
        .total-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        
        .total-amount {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .total-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .payment-info {
            background: #f0fff4;
            border: 1px solid #9ae6b4;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .due-date {
            color: #e53e3e;
            font-weight: 600;
            font-size: 16px;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #718096;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .challan-number {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="challan">
        <div class="header">
            <div class="challan-number">
                Challan #{{ str_pad($student->id, 6, '0', STR_PAD_LEFT) }}
            </div>
            <div class="school-info">
                <div class="school-name">{{ config('app.school_name', 'Excellence Academy') }}</div>
                <p>{{ config('app.school_address', 'Education Street, Knowledge City') }}</p>
                <div class="challan-title">Monthly Fee Challan</div>
            </div>
        </div>
        
        <div class="content">
            <div class="student-info">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Student Name</div>
                        <div class="info-value">{{ $student->first_name }} {{ $student->last_name }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Roll Number</div>
                        <div class="info-value">{{ $student->roll_number }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Class</div>
                        <div class="info-value">{{ $student->class->name ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Father Name</div>
                        <div class="info-value">{{ $student->father_name ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
            
            <table class="fee-table">
                <thead>
                    <tr>
                        <th>Fee Description</th>
                        <th>Amount</th>
                        <th>Month</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Monthly Tuition Fee</td>
                        <td>Rs. 5,000</td>
                        <td>{{ date('F Y') }}</td>
                    </tr>
                    <tr>
                        <td>Library Fee</td>
                        <td>Rs. 300</td>
                        <td>{{ date('F Y') }}</td>
                    </tr>
                    <tr>
                        <td>Laboratory Fee</td>
                        <td>Rs. 500</td>
                        <td>{{ date('F Y') }}</td>
                    </tr>
                    <tr>
                        <td>Sports Fee</td>
                        <td>Rs. 200</td>
                        <td>{{ date('F Y') }}</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="total-section">
                <div class="total-amount">Rs. 6,000</div>
                <div class="total-label">Total Amount Payable</div>
            </div>
            
            <div class="payment-info">
                <p><strong>Payment Instructions:</strong></p>
                <ul>
                    <li>Please pay fees before the due date to avoid late fee charges</li>
                    <li>Payment can be made at school office or through bank transfer</li>
                    <li>Keep this challan as proof of payment</li>
                </ul>
                <p class="due-date">Due Date: {{ date('d M, Y', strtotime('+15 days')) }}</p>
            </div>
            
            <div class="footer">
                <p>Generated on {{ date('d M, Y h:i A') }} | For queries, contact: admin@school.edu</p>
            </div>
        </div>
    </div>
</body>
</html>
