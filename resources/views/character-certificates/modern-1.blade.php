@extends('layouts.certificate')

@section('content')
<div class="certificate modern-style-1">
    <h1>Character Certificate</h1>
    <p>This is to certify that <strong>{{ $student->name ?? '' }}</strong>, S/O <strong>{{ $student->father_name ?? '' }}</strong>,
        bearing Roll No. <strong>{{ $student->roll_number }}</strong> has been a student of <strong>{{ $school->name }}</strong>.
    </p>
    <p>He/She has shown good character and conduct during his/her stay at this institution.</p>
    <p>Date of Issue: <strong>{{ date('d-m-Y') }}</strong></p>
    <div class="signature-block">
        <span>Principal</span>
    </div>
</div>
@endsection 