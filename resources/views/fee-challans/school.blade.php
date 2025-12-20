<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Challan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .challan-table { width: 100%; border-collapse: collapse; }
        .challan-copy { width: 33.3%; border: 1px solid #000; vertical-align: top; padding: 0 6px; }
        .challan-header { text-align: center; font-weight: bold; font-size: 15px; }
        .challan-subheader { text-align: center; font-size: 12px; }
        .challan-section-title { text-align: center; font-size: 13px; font-weight: bold; margin: 4px 0; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .info-table td { padding: 2px 4px; border: 1px solid #000; }
        .info-table tr td:first-child { font-weight: bold; width: 45%; }
        .fee-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .fee-table th, .fee-table td { border: 1px solid #000; padding: 2px 4px; text-align: left; }
        .fee-table th { background: #f8f8f8; }
        .total-row { font-weight: bold; }
        .red { color: #d32f2f; font-weight: bold; }
        .footer { font-size: 11px; margin-top: 4px; }
        .amount-words { font-size: 11px; font-weight: bold; }
    </style>
</head>
<body>
<table class="challan-table">
    <tr>
        @foreach(['School/College Copy', 'Bank Copy', 'Student Copy'] as $copyType)
        <td class="challan-copy">
            <div class="challan-header">Excellent Education System (Demo)</div>
            <div class="challan-subheader">G-11 Markaz Islamabad<br>Phone Number : 03114443493</div>
            <div class="challan-section-title">{{ $copyType }}</div>
            <table class="info-table">
                <tr><td>Challan Form No</td><td>{{ $challanNumber ?? '10' }}</td></tr>
                <tr><td>Due Date</td><td>{{ $dueDate ?? '10-Nov-2020' }}</td></tr>
                <tr><td>Valid Till</td><td>{{ $validTill ?? '30-Nov-2020' }}</td></tr>
                <tr><td>Student Reg No</td><td>{{ $student->reg_no ?? '00126631' }}</td></tr>
                <tr><td>Student Name</td><td>{{ $student->first_name }} {{ $student->last_name }}</td></tr>
                <tr><td>Father Name</td><td>{{ $student->father_name ?? 'Ameer Abbas Awan' }}</td></tr>
                <tr><td>Class</td><td>{{ optional($student->class)->name ?? '9th - Quaid' }}</td></tr>
            </table>
            <table class="fee-table">
                <thead>
                    <tr><th>Description</th><th>Amount</th></tr>
                </thead>
                <tbody>
                    <tr><td>Monthly fee</td><td>{{ $feeDetails['monthly_fee'] ?? '1000' }}</td></tr>
                    <tr><td>Generator Fund</td><td>{{ $feeDetails['generator_fund'] ?? '100' }}</td></tr>
                    <tr><td>Previous Fee</td><td>{{ $feeDetails['previous_fee'] ?? '0' }}</td></tr>
                    <tr class="total-row"><td>Total Fee</td><td>{{ $feeDetails['total_fee'] ?? '1100' }}</td></tr>
                    <tr><td>Discount/Scholarship</td><td>{{ $feeDetails['discount'] ?? '100' }}</td></tr>
                    <tr><td>Fee Within Due Date <span style="font-size:10px;">( Till {{ $dueDate ?? '10-Nov-2020' }} )</span></td><td>{{ $feeDetails['fee_within_due'] ?? '1000' }}</td></tr>
                    <tr><td class="red">Fee After Due Date <span style="font-size:10px;">( From {{ $afterDueDate ?? '11-Nov-2020' }} )</span></td><td class="red">{{ $feeDetails['fee_after_due'] ?? '1000' }}</td></tr>
                </tbody>
            </table>
            <div class="footer">Amount in words : <span class="amount-words">{{ $amountInWords ?? 'One thousand' }}</span></div>
            <div style="margin-top:18px; width:100%; font-size:11px;">
                <div style="float:left; text-align:left; width:40%;">
                    _____________<br>Clerk
                </div>
                <div style="float:right; text-align:right; width:40%;">
                   _____________<br>Principal
                </div>
                <div style="clear:both;"></div>
            </div>
        </td>
        @endforeach
    </tr>
</table>
</body>
</html> 