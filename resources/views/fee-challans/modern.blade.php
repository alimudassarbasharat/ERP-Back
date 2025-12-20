<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Askari Bank/NUML Fee Challan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .challan-table { width: 100%; border-collapse: collapse; }
        .challan-copy { width: 25%; border: 1px solid #000; vertical-align: top; padding: 0 4px; }
        .logo-row td { text-align: center; }
        .logo-img { height: 32px; }
        .header { text-align: center; font-weight: bold; font-size: 13px; }
        .subheader { text-align: center; font-size: 11px; }
        .account-title { text-align: center; font-size: 12px; font-weight: bold; }
        .credit-account { text-align: center; font-size: 12px; font-weight: bold; }
        .section-title { text-align: center; font-size: 13px; font-weight: bold; margin: 4px 0; }
        .info-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 2px; }
        .info-table td { padding: 1px 2px; border: none; }
        .info-table tr td:first-child { width: 45%; font-weight: bold; }
        .fee-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 2px; }
        .fee-table th, .fee-table td { border: 1px solid #000; padding: 2px 4px; text-align: left; }
        .fee-table th { background: #f8f8f8; text-align: left; }
        .total-row td { font-weight: bold; }
        .footer-note { font-size: 10px; margin-top: 6px; }
        .bank-stamp { text-align: center; font-size: 11px; margin-top: 10px; font-weight: bold; }
        .dashed { border-right: 2px dashed #000; }
    </style>
</head>
<body>
<table class="challan-table">
    <tr>
        <td class="challan-copy dashed">
            <table style="width:100%;">
                <tr class="logo-row">
                    <td><img src="{{ public_path('askari-logo.png') }}" class="logo-img" alt="Bank Logo" /></td>
                    <td><img src="{{ public_path('numl-logo.png') }}" class="logo-img" alt="NUML Logo" /></td>
                </tr>
            </table>
            <div class="header">Askari Bank Limited<br><span style="font-weight:normal;">The Power To Lead</span></div>
            <div class="subheader">National University of Modern Languages<br>Islamabad Campus.</div>
            <div class="subheader">Remote Branch: Industrial Area Branch I-9, Islamabad - 0555<br>A/C Title: NUML Islamabad Campus Fee Collection</div>
            <div class="section-title">Admission Spring 2017</div>
            <div class="credit-account">Credit Bank Account#: 551650500720</div>
            <table class="info-table">
                <tr><td>Admission Form #</td><td>{{ $admissionFormNo ?? '' }}</td></tr>
                <tr><td>Name:</td><td>{{ $student->first_name }} {{ $student->last_name }}</td></tr>
                <tr><td>Program Applying:</td><td>{{ $programApplying ?? '' }}</td></tr>
                <tr><td>Challan Form #</td><td>{{ $challanNumber ?? '' }}</td></tr>
                <tr><td>Issue Date</td><td>{{ $issueDate ?? '' }}</td></tr>
            </table>
            <table class="fee-table">
                <thead>
                    <tr><th>Description</th><th>Amount (Rupees)</th></tr>
                </thead>
                <tbody>
                    <tr><td>Admission Fee</td><td>{{ $feeDetails['admission_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Caution Money (Refundable)</td><td>{{ $feeDetails['caution_money'] ?? '0.00' }}</td></tr>
                    <tr><td>Form Processing Fee</td><td>{{ $feeDetails['form_processing_fee'] ?? '1000.00' }}</td></tr>
                    <tr><td>Registration Fee</td><td>{{ $feeDetails['registration_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Examination Fee</td><td>{{ $feeDetails['examination_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Library Fee</td><td>{{ $feeDetails['library_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Laboratory Fee</td><td>{{ $feeDetails['laboratory_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Sports Fee</td><td>{{ $feeDetails['sports_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Student Club</td><td>{{ $feeDetails['student_club'] ?? '0.00' }}</td></tr>
                    <tr><td>Tuition Fee</td><td>{{ $feeDetails['tuition_fee'] ?? '0.00' }}</td></tr>
                    <tr class="total-row"><td>TOTAL PAYMENT</td><td>{{ $feeDetails['total_payment'] ?? '1000.00' }}</td></tr>
                </tbody>
            </table>
            <div class="footer-note">1. Desired Bank Stamp is Required on the Deposit Slip and Send Original Deposit Slip (NUML Copy) along Application Form to NUML Office.<br>2. Application Form will not be Entertained without original Deposit Slip (NUML Copy).</div>
            <div style="margin-top:18px; width:100%; font-size:11px;">
                <div style="float:left; text-align:left; width:40%;">
                    ___________________<br>Clerk
                </div>
                <div style="float:right; text-align:right; width:40%;">
                    ___________________<br>Principal
                </div>
                <div style="clear:both;"></div>
            </div>
            <div class="bank-stamp">Bank Stamp</div>
        </td>
        <td class="challan-copy dashed">
            <table style="width:100%;">
                <tr class="logo-row">
                    <td><img src="{{ public_path('askari-logo.png') }}" class="logo-img" alt="Bank Logo" /></td>
                    <td><img src="{{ public_path('numl-logo.png') }}" class="logo-img" alt="NUML Logo" /></td>
                </tr>
            </table>
            <div class="header">Askari Bank Limited<br><span style="font-weight:normal;">The Power To Lead</span></div>
            <div class="subheader">National University of Modern Languages<br>Islamabad Campus.</div>
            <div class="subheader">Remote Branch: Industrial Area Branch I-9, Islamabad - 0555<br>A/C Title: NUML Islamabad Campus Fee Collection</div>
            <div class="section-title">Admission Spring 2017</div>
            <div class="credit-account">Credit Bank Account#: 551650500720</div>
            <table class="info-table">
                <tr><td>Admission Form #</td><td>{{ $admissionFormNo ?? '' }}</td></tr>
                <tr><td>Name:</td><td>{{ $student->first_name }} {{ $student->last_name }}</td></tr>
                <tr><td>Program Applying:</td><td>{{ $programApplying ?? '' }}</td></tr>
                <tr><td>Challan Form #</td><td>{{ $challanNumber ?? '' }}</td></tr>
                <tr><td>Issue Date</td><td>{{ $issueDate ?? '' }}</td></tr>
            </table>
            <table class="fee-table">
                <thead>
                    <tr><th>Description</th><th>Amount (Rupees)</th></tr>
                </thead>
                <tbody>
                    <tr><td>Admission Fee</td><td>{{ $feeDetails['admission_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Caution Money (Refundable)</td><td>{{ $feeDetails['caution_money'] ?? '0.00' }}</td></tr>
                    <tr><td>Form Processing Fee</td><td>{{ $feeDetails['form_processing_fee'] ?? '1000.00' }}</td></tr>
                    <tr><td>Registration Fee</td><td>{{ $feeDetails['registration_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Examination Fee</td><td>{{ $feeDetails['examination_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Library Fee</td><td>{{ $feeDetails['library_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Laboratory Fee</td><td>{{ $feeDetails['laboratory_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Sports Fee</td><td>{{ $feeDetails['sports_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Student Club</td><td>{{ $feeDetails['student_club'] ?? '0.00' }}</td></tr>
                    <tr><td>Tuition Fee</td><td>{{ $feeDetails['tuition_fee'] ?? '0.00' }}</td></tr>
                    <tr class="total-row"><td>TOTAL PAYMENT</td><td>{{ $feeDetails['total_payment'] ?? '1000.00' }}</td></tr>
                </tbody>
            </table>
            <div class="footer-note">1. Desired Bank Stamp is Required on the Deposit Slip and Send Original Deposit Slip (NUML Copy) along Application Form to NUML Office.<br>2. Application Form will not be Entertained without original Deposit Slip (NUML Copy).</div>
            <div style="margin-top:18px; width:100%; font-size:11px;">
                <div style="float:left; text-align:left; width:40%;">
                    ___________________<br>Clerk
                </div>
                <div style="float:right; text-align:right; width:40%;">
                    ___________________<br>Principal
                </div>
                <div style="clear:both;"></div>
            </div>
            <div class="bank-stamp">Bank Stamp</div>
        </td>
        <td class="challan-copy">
            <table style="width:100%;">
                <tr class="logo-row">
                    <td><img src="{{ public_path('askari-logo.png') }}" class="logo-img" alt="Bank Logo" /></td>
                    <td><img src="{{ public_path('numl-logo.png') }}" class="logo-img" alt="NUML Logo" /></td>
                </tr>
            </table>
            <div class="header">Askari Bank Limited<br><span style="font-weight:normal;">The Power To Lead</span></div>
            <div class="subheader">National University of Modern Languages<br>Islamabad Campus.</div>
            <div class="subheader">Remote Branch: Industrial Area Branch I-9, Islamabad - 0555<br>A/C Title: NUML Islamabad Campus Fee Collection</div>
            <div class="section-title">Admission Spring 2017</div>
            <div class="credit-account">Credit Bank Account#: 551650500720</div>
            <table class="info-table">
                <tr><td>Admission Form #</td><td>{{ $admissionFormNo ?? '' }}</td></tr>
                <tr><td>Name:</td><td>{{ $student->first_name }} {{ $student->last_name }}</td></tr>
                <tr><td>Program Applying:</td><td>{{ $programApplying ?? '' }}</td></tr>
                <tr><td>Challan Form #</td><td>{{ $challanNumber ?? '' }}</td></tr>
                <tr><td>Issue Date</td><td>{{ $issueDate ?? '' }}</td></tr>
            </table>
            <table class="fee-table">
                <thead>
                    <tr><th>Description</th><th>Amount (Rupees)</th></tr>
                </thead>
                <tbody>
                    <tr><td>Admission Fee</td><td>{{ $feeDetails['admission_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Caution Money (Refundable)</td><td>{{ $feeDetails['caution_money'] ?? '0.00' }}</td></tr>
                    <tr><td>Form Processing Fee</td><td>{{ $feeDetails['form_processing_fee'] ?? '1000.00' }}</td></tr>
                    <tr><td>Registration Fee</td><td>{{ $feeDetails['registration_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Examination Fee</td><td>{{ $feeDetails['examination_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Library Fee</td><td>{{ $feeDetails['library_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Laboratory Fee</td><td>{{ $feeDetails['laboratory_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Sports Fee</td><td>{{ $feeDetails['sports_fee'] ?? '0.00' }}</td></tr>
                    <tr><td>Student Club</td><td>{{ $feeDetails['student_club'] ?? '0.00' }}</td></tr>
                    <tr><td>Tuition Fee</td><td>{{ $feeDetails['tuition_fee'] ?? '0.00' }}</td></tr>
                    <tr class="total-row"><td>TOTAL PAYMENT</td><td>{{ $feeDetails['total_payment'] ?? '1000.00' }}</td></tr>
                </tbody>
            </table>
            <div class="footer-note">1. Desired Bank Stamp is Required on the Deposit Slip and Send Original Deposit Slip (NUML Copy) along Application Form to NUML Office.<br>2. Application Form will not be Entertained without original Deposit Slip (NUML Copy).</div>
            <div style="margin-top:18px; width:100%; font-size:11px;">
                <div style="float:left; text-align:left; width:40%;">
                    ___________________<br>Clerk
                </div>
                <div style="float:right; text-align:right; width:40%;">
                    ___________________<br>Principal
                </div>
                <div style="clear:both;"></div>
            </div>
            <div class="bank-stamp">Bank Stamp</div>
        </td>
    </tr>
</table>
</body>
</html> 