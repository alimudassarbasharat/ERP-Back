<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bank Challan - Medical Colleges of Balochistan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .challan-table { width: 100%; border-collapse: collapse; }
        .challan-copy { width: 25%; border: 1px solid #000; vertical-align: top; padding: 4px 6px; }
        .logo { text-align: center; margin-bottom: 2px; }
        .logo img { height: 40px; }
        .header { text-align: center; font-weight: bold; font-size: 13px; }
        .subheader { text-align: center; font-size: 11px; }
        .account { text-align: center; font-size: 13px; font-weight: bold; margin: 2px 0; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; font-size: 11px; }
        .info-table td { padding: 1px 2px; }
        .info-table tr td:first-child { width: 40%; }
        .fee-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; font-size: 11px; }
        .fee-table th, .fee-table td { border: 1px solid #000; padding: 2px 4px; text-align: left; }
        .fee-table th { background: #f8f8f8; text-align: center; }
        .footer { font-size: 11px; margin-top: 8px; }
        .sign-row { width: 100%; margin-top: 12px; font-size: 11px; }
        .sign-cell { width: 50%; text-align: center; display: inline-block; }
        .deposited { font-size: 10px; margin-top: 8px; text-align: left; }
        .dashed { border-right: 2px dashed #000; }
    </style>
</head>
<body>
<table class="challan-table">
    <tr>
        @foreach(['Bank Copy', 'Admission Branch Copy', 'Cashier BMC Copy', "Candidate's Copy"] as $copyType)
        <td class="challan-copy @if(!$loop->last)dashed@endif">
            <div class="logo">
                <img src="{{ public_path('logo-placeholder.png') }}" alt="Logo" />
            </div>
            <div class="header">ACADEMIC SESSION 2023-24 FOR (MBBS, BDS)<br>MEDICAL COLLEGES OF BALOCHISTAN</div>
            <div class="subheader">NATIONAL BANK OF PAKISTAN<br>BOLAN MEDICAL COLLEGE, (BRANCH) QUETTA</div>
            <div class="info-table">
                <table style="width:100%;">
                    <tr><td>Bank Challan No</td><td>{{ $challanNumber ?? '' }}</td></tr>
                    <tr><td>Date</td><td>{{ $issueDate ?? '' }}</td></tr>
                </table>
            </div>
            <div class="account">(A/C:0184-4014858565)</div>
            <div class="info-table">
                <table style="width:100%;">
                    <tr><td>Name of Student<br><span style="font-size:10px;">(in block Letters)</span></td><td>{{ $student->first_name }} {{ $student->last_name }}</td></tr>
                    <tr><td>Father's Name</td><td>{{ $student->father_name ?? '' }}</td></tr>
                </table>
            </div>
            <table class="fee-table">
                <thead>
                    <tr><th style="width:20px;">S.NO</th><th>Particulars</th><th>Amount</th></tr>
                </thead>
                <tbody>
                    <tr><td>1.</td><td>Merit Seat</td><td>{{ $feeDetails['merit_seat'] ?? 'Rs.' }}</td></tr>
                    <tr><td>2.</td><td>Category Minority</td><td>{{ $feeDetails['category_minority'] ?? 'Rs.' }}</td></tr>
                    <tr><td>3.</td><td>Category Disabled</td><td>{{ $feeDetails['category_disabled'] ?? 'Rs.' }}</td></tr>
                    <tr><td colspan="2" style="text-align:right;font-weight:bold;">Total</td><td>{{ $feeDetails['total'] ?? 'Rs.' }}</td></tr>
                </tbody>
            </table>
            <div class="footer">
                Rupees: ____________________________________________
            </div>
            <div class="sign-row">
                <div class="sign-cell">Cashier</div>
                <div class="sign-cell">Officer</div>
            </div>
            <div class="deposited">Deposited By ____________________________</div>
        </td>
        @endforeach
    </tr>
</table>
</body>
</html> 