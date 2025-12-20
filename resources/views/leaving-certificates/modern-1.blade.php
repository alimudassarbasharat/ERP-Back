@extends('layouts.certificate')

@section('content')
<div class="certificate modern-style-1">
    <h1>Leaving Certificate</h1>
    <p>This is to certify that <strong>{{ $student->name }}</strong>, S/O <strong>{{ $student->father_name }}</strong>,
        bearing Roll No. <strong>{{ $student->roll_number }}</strong> has left <strong>{{ $school->name }}</strong> on <strong>{{ date('d-m-Y') }}</strong>.</p>
    <p>He/She was a student of good character and conduct.</p>
    <p>Date of Issue: <strong>{{ date('d-m-Y') }}</strong></p>
    <div class="signature-block">
        <span>Principal</span>
    </div>
</div>
@endsection 