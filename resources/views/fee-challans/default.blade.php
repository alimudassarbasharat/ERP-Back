<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HBL MINHAJ UNIVERSITY Lahore - Fee Challan</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 12px;
        }
        .challan-container {
            border: 2px solid #000;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: bold;
            font-size: 15px;
            line-height: 1.1;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .school-address {
            font-size: 14px;
            margin-bottom: 5px;
        }
        .challan-title {
            font-size: 20px;
            font-weight: bold;
            margin: 10px 0;
        }
        .challan-details {
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        .detail-label {
            width: 150px;
            font-weight: bold;
        }
        .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .fee-table th, .fee-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .fee-table th {
            background-color: #f0f0f0;
        }
        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 200px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 5px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            opacity: 0.1;
            color: #000;
            z-index: -1;
        }
        .challan-table { width: 100%; border-collapse: collapse; }
        .challan-copy { width: 33.3%; border: 1px solid #000; vertical-align: top; padding: 4px 6px; }
        .subheader { font-size: 12px; font-weight: bold; }
        .copy-type { font-size: 12px; font-weight: bold; text-align: right; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; font-size: 11px; }
        .info-table td { padding: 1px 2px; }
        .info-table tr td:first-child { width: 38%; font-weight: bold; }
        .fee-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; font-size: 11px; }
        .fee-table th, .fee-table td { border: 1px solid #000; padding: 2px 4px; text-align: left; }
        .fee-table th { background: #f8f8f8; text-align: left; }
        .fee-table .total-row td { font-weight: bold; }
        .footer { font-size: 11px; margin-top: 8px; }
        .rupees { font-style: italic; font-size: 11px; }
        .sign-row { width: 100%; margin-top: 12px; font-size: 11px; text-align: right; }
        .issue-date { font-size: 10px; margin-top: 2px; }
        .dashed { border-right: 2px dashed #000; }
    </style>
</head>
<body>
    <div class="challan-container" style="padding:0;max-width:none;width:1000px;">
        <table class="challan-table">
            <tr>
                <td class="challan-copy dashed">
                    <div class="header">HBL<br>MINHAJ UNIVERSITY Lahore</div>
                    <div class="subheader">HBL Collection Account # 0042-79015260-03</div>
                    {{-- <div class="copy-type">Bank</div> --}}
                    <table class="info-table">
                        <tr><td>Challan #:</td><td>{{ $challanNumber ?? '' }}</td></tr>
                        <tr><td class="copy-type">Issue Date:</td><td>{{ $issueDate ?? '' }}</td></tr>
                        <tr><td>Reg/Form#:</td><td>{{ $student->reg_form ?? '' }}</td></tr>
                        <tr><td class="copy-type">Due Date:</td><td>{{ $dueDate ?? '' }}</td></tr>
                        <tr><td>Name:</td><td>{{ $student->first_name }} {{ $student->last_name }}</td></tr>
                        <tr><td class="copy-type">Class:</td><td>{{ optional($student->class)->name ?? '' }}</td></tr>
                        <tr><td>Roll No:</td><td>{{ $student->roll_number ?? '' }}</td></tr>
                        <tr><td class="copy-type">Semester:</td><td>{{ $student->semester ?? '' }}</td></tr>
                        <tr><td>Session:</td><td>{{ $student->session ?? '' }}</td></tr>
                    </table>
                    <table class="fee-table">
                        <thead>
                            <tr><th>Description</th><th>Amount (Rs.)</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Admission Fee</td><td>{{ $feeDetails['admission_fee'] ?? '' }}</td></tr>
                            <tr><td>Tuition Fee</td><td>{{ $feeDetails['tuition_fee'] ?? '' }}</td></tr>
                            <tr><td>Web Portal Fee</td><td>{{ $feeDetails['web_portal_fee'] ?? '' }}</td></tr>
                            <tr><td>Migration Fee</td><td>{{ $feeDetails['migration_fee'] ?? '' }}</td></tr>
                            <tr><td>Library & Magazine Fund</td><td>{{ $feeDetails['library_fund'] ?? '' }}</td></tr>
                            <tr><td>Building Fund</td><td>{{ $feeDetails['building_fund'] ?? '' }}</td></tr>
                            <tr><td>Lab Development Fund</td><td>{{ $feeDetails['lab_fund'] ?? '' }}</td></tr>
                            <tr><td>Registration Fee</td><td>{{ $feeDetails['registration_fee'] ?? '' }}</td></tr>
                            <tr><td>Transcript / Degree Fee</td><td>{{ $feeDetails['transcript_fee'] ?? '' }}</td></tr>
                            <tr><td>Furniture Fund</td><td>{{ $feeDetails['furniture_fund'] ?? '' }}</td></tr>
                            <tr><td>Security Fee (Refundable)</td><td>{{ $feeDetails['security_fee'] ?? '' }}</td></tr>
                            <tr><td>Hostel Fee</td><td>{{ $feeDetails['hostel_fee'] ?? '' }}</td></tr>
                            <tr><td>Examination Fee</td><td>{{ $feeDetails['examination_fee'] ?? '' }}</td></tr>
                            <tr><td>Remaining Tuition Fee</td><td>{{ $feeDetails['remaining_tuition_fee'] ?? '' }}</td></tr>
                            <tr><td>Other Charges</td><td>{{ $feeDetails['other_charges'] ?? '' }}</td></tr>
                            <tr><td>Repeat Semester/Paper Fee</td><td>{{ $feeDetails['repeat_fee'] ?? '' }}</td></tr>
                            <tr><td>Student Card Fee</td><td>{{ $feeDetails['student_card_fee'] ?? '' }}</td></tr>
                            <tr><td>Remaining Hostel Fee</td><td>{{ $feeDetails['remaining_hostel_fee'] ?? '' }}</td></tr>
                            <tr><td>Misc</td><td>{{ $feeDetails['misc'] ?? '' }}</td></tr>
                            <tr class="total-row"><td>Grand Total</td><td>{{ $feeDetails['grand_total'] ?? '' }}</td></tr>
                        </tbody>
                    </table>
                    <div class="footer rupees">Rupees in word: <span>{{ $amountInWords ?? '' }}</span></div>
                    <div class="sign-row">
                        <span class="issue-date">issue Date: {{ $issueDate ?? '' }}</span> &nbsp;&nbsp;&nbsp; Cashier
                    </div>
                </td>
                <td class="challan-copy dashed">
                    <div class="header">HBL<br>MINHAJ UNIVERSITY Lahore</div>
                    <div class="subheader">HBL Collection Account # 0042-79015260-03</div>
                    <div class="copy-type">Account</div>
                    <table class="info-table">
                        <tr><td>Challan #:</td><td>{{ $challanNumber ?? '' }}</td></tr>
                        <tr><td>Issue Date:</td><td>{{ $issueDate ?? '' }}</td></tr>
                        <tr><td>Reg/Form#:</td><td>{{ $student->reg_form ?? '' }}</td></tr>
                        <tr><td>Due Date:</td><td>{{ $dueDate ?? '' }}</td></tr>
                        <tr><td>Name:</td><td>{{ $student->first_name }} {{ $student->last_name }}</td></tr>
                        <tr><td>Class:</td><td>{{ optional($student->class)->name ?? '' }}</td></tr>
                        <tr><td>Roll No:</td><td>{{ $student->roll_number ?? '' }}</td></tr>
                        <tr><td>Semester:</td><td>{{ $student->semester ?? '' }}</td></tr>
                        <tr><td>Session:</td><td>{{ $student->session ?? '' }}</td></tr>
                    </table>
                    <table class="fee-table">
                        <thead>
                            <tr><th>Description</th><th>Amount (Rs.)</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Admission Fee</td><td>{{ $feeDetails['admission_fee'] ?? '' }}</td></tr>
                            <tr><td>Tuition Fee</td><td>{{ $feeDetails['tuition_fee'] ?? '' }}</td></tr>
                            <tr><td>Web Portal Fee</td><td>{{ $feeDetails['web_portal_fee'] ?? '' }}</td></tr>
                            <tr><td>Migration Fee</td><td>{{ $feeDetails['migration_fee'] ?? '' }}</td></tr>
                            <tr><td>Library & Magazine Fund</td><td>{{ $feeDetails['library_fund'] ?? '' }}</td></tr>
                            <tr><td>Building Fund</td><td>{{ $feeDetails['building_fund'] ?? '' }}</td></tr>
                            <tr><td>Lab Development Fund</td><td>{{ $feeDetails['lab_fund'] ?? '' }}</td></tr>
                            <tr><td>Registration Fee</td><td>{{ $feeDetails['registration_fee'] ?? '' }}</td></tr>
                            <tr><td>Transcript / Degree Fee</td><td>{{ $feeDetails['transcript_fee'] ?? '' }}</td></tr>
                            <tr><td>Furniture Fund</td><td>{{ $feeDetails['furniture_fund'] ?? '' }}</td></tr>
                            <tr><td>Security Fee (Refundable)</td><td>{{ $feeDetails['security_fee'] ?? '' }}</td></tr>
                            <tr><td>Hostel Fee</td><td>{{ $feeDetails['hostel_fee'] ?? '' }}</td></tr>
                            <tr><td>Examination Fee</td><td>{{ $feeDetails['examination_fee'] ?? '' }}</td></tr>
                            <tr><td>Remaining Tuition Fee</td><td>{{ $feeDetails['remaining_tuition_fee'] ?? '' }}</td></tr>
                            <tr><td>Other Charges</td><td>{{ $feeDetails['other_charges'] ?? '' }}</td></tr>
                            <tr><td>Repeat Semester/Paper Fee</td><td>{{ $feeDetails['repeat_fee'] ?? '' }}</td></tr>
                            <tr><td>Student Card Fee</td><td>{{ $feeDetails['student_card_fee'] ?? '' }}</td></tr>
                            <tr><td>Remaining Hostel Fee</td><td>{{ $feeDetails['remaining_hostel_fee'] ?? '' }}</td></tr>
                            <tr><td>Misc</td><td>{{ $feeDetails['misc'] ?? '' }}</td></tr>
                            <tr class="total-row"><td>Grand Total</td><td>{{ $feeDetails['grand_total'] ?? '' }}</td></tr>
                        </tbody>
                    </table>
                    <div class="footer rupees">Rupees in word: <span>{{ $amountInWords ?? '' }}</span></div>
                    <div class="sign-row">
                        <span class="issue-date">issue Date: {{ $issueDate ?? '' }}</span> &nbsp;&nbsp;&nbsp; Cashier
                    </div>
                </td>
                <td class="challan-copy">
                    <div class="header">HBL<br>MINHAJ UNIVERSITY Lahore</div>
                    <div class="subheader">HBL Collection Account # 0042-79015260-03</div>
                    <div class="copy-type">Student's</div>
                    <table class="info-table">
                        <tr><td>Challan #:</td><td>{{ $challanNumber ?? '' }}</td></tr>
                        <tr><td>Issue Date:</td><td>{{ $issueDate ?? '' }}</td></tr>
                        <tr><td>Reg/Form#:</td><td>{{ $student->reg_form ?? '' }}</td></tr>
                        <tr><td>Due Date:</td><td>{{ $dueDate ?? '' }}</td></tr>
                        <tr><td>Name:</td><td>{{ $student->first_name }} {{ $student->last_name }}</td></tr>
                        <tr><td>Class:</td><td>{{ optional($student->class)->name ?? '' }}</td></tr>
                        <tr><td>Roll No:</td><td>{{ $student->roll_number ?? '' }}</td></tr>
                        <tr><td>Semester:</td><td>{{ $student->semester ?? '' }}</td></tr>
                        <tr><td>Session:</td><td>{{ $student->session ?? '' }}</td></tr>
                    </table>
                    <table class="fee-table">
                        <thead>
                            <tr><th>Description</th><th>Amount (Rs.)</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Admission Fee</td><td>{{ $feeDetails['admission_fee'] ?? '' }}</td></tr>
                            <tr><td>Tuition Fee</td><td>{{ $feeDetails['tuition_fee'] ?? '' }}</td></tr>
                            <tr><td>Web Portal Fee</td><td>{{ $feeDetails['web_portal_fee'] ?? '' }}</td></tr>
                            <tr><td>Migration Fee</td><td>{{ $feeDetails['migration_fee'] ?? '' }}</td></tr>
                            <tr><td>Library & Magazine Fund</td><td>{{ $feeDetails['library_fund'] ?? '' }}</td></tr>
                            <tr><td>Building Fund</td><td>{{ $feeDetails['building_fund'] ?? '' }}</td></tr>
                            <tr><td>Lab Development Fund</td><td>{{ $feeDetails['lab_fund'] ?? '' }}</td></tr>
                            <tr><td>Registration Fee</td><td>{{ $feeDetails['registration_fee'] ?? '' }}</td></tr>
                            <tr><td>Transcript / Degree Fee</td><td>{{ $feeDetails['transcript_fee'] ?? '' }}</td></tr>
                            <tr><td>Furniture Fund</td><td>{{ $feeDetails['furniture_fund'] ?? '' }}</td></tr>
                            <tr><td>Security Fee (Refundable)</td><td>{{ $feeDetails['security_fee'] ?? '' }}</td></tr>
                            <tr><td>Hostel Fee</td><td>{{ $feeDetails['hostel_fee'] ?? '' }}</td></tr>
                            <tr><td>Examination Fee</td><td>{{ $feeDetails['examination_fee'] ?? '' }}</td></tr>
                            <tr><td>Remaining Tuition Fee</td><td>{{ $feeDetails['remaining_tuition_fee'] ?? '' }}</td></tr>
                            <tr><td>Other Charges</td><td>{{ $feeDetails['other_charges'] ?? '' }}</td></tr>
                            <tr><td>Repeat Semester/Paper Fee</td><td>{{ $feeDetails['repeat_fee'] ?? '' }}</td></tr>
                            <tr><td>Student Card Fee</td><td>{{ $feeDetails['student_card_fee'] ?? '' }}</td></tr>
                            <tr><td>Remaining Hostel Fee</td><td>{{ $feeDetails['remaining_hostel_fee'] ?? '' }}</td></tr>
                            <tr><td>Misc</td><td>{{ $feeDetails['misc'] ?? '' }}</td></tr>
                            <tr class="total-row"><td>Grand Total</td><td>{{ $feeDetails['grand_total'] ?? '' }}</td></tr>
                        </tbody>
                    </table>
                    <div class="footer rupees">Rupees in word: <span>{{ $amountInWords ?? '' }}</span></div>
                    <div class="sign-row">
                        <span class="issue-date">issue Date: {{ $issueDate ?? '' }}</span> &nbsp;&nbsp;&nbsp; Cashier
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html> 