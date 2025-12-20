@extends('layouts.certificate')

@section('content')
<div class="certificate modern-style-3">
    <h1>Character Certificate</h1>
    <p>This is to certify that <strong>{{ $student->name }}</strong>, S/O <strong>{{ $student->father_name }}</strong>,
        Roll No. <strong>{{ $student->roll_number }}</strong>, attended <strong>{{ $school->name }}</strong> from <strong>{{ $student->admission_date ?? '_____' }}</strong> to <strong>{{ $student->leaving_date ?? '_____' }}</strong>.</p>
    <p>He/She has been honest, diligent, and respectful throughout his/her studies.</p>
    <p>Date: <strong>{{ date('d-m-Y') }}</strong></p>
    <div class="signature-block">
        <span>School Seal</span>
        <span>Principal</span>
    </div>
</div>
@endsection 