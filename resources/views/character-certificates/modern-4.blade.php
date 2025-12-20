@extends('layouts.certificate')

@section('content')
<div class="certificate modern-style-4">
    <h1>Character Certificate</h1>
    <p>This is to certify that <strong>{{ $student->name }}</strong>, S/O <strong>{{ $student->father_name }}</strong>,
        Roll No. <strong>{{ $student->roll_number }}</strong>, has been a student at <strong>{{ $school->name }}</strong> for the session <strong>{{ $student->session ?? '_____' }}</strong>.</p>
    <p>He/She has maintained a satisfactory record of conduct and discipline.</p>
    <p>Certificate issued on: <strong>{{ date('d-m-Y') }}</strong></p>
    <div class="signature-block">
        <span>Authorized Signatory</span>
    </div>
</div>
@endsection 