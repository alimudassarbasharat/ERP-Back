@extends('layouts.certificate')

@section('content')
<div class="certificate modern-style-5">
    <h1>Character Certificate</h1>
    <p>This is to certify that <strong>{{ $student->name }}</strong>, S/O <strong>{{ $student->father_name }}</strong>,
        Roll No. <strong>{{ $student->roll_number }}</strong>, has been a regular student at <strong>{{ $school->name }}</strong>.</p>
    <p>He/She has always shown respect towards teachers and peers and maintained a positive attitude.</p>
    <p>Issued: <strong>{{ date('d-m-Y') }}</strong></p>
    <div class="signature-block">
        <span>Principal</span>
        <span>School Stamp</span>
    </div>
</div>
@endsection 