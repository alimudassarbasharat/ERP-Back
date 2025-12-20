@extends('layouts.certificate')

@section('content')
<div class="certificate modern-style-2">
    <h1>Leaving Certificate</h1>
    <p>This is to certify that <strong>{{ $student->name }}</strong>, S/O <strong>{{ $student->father_name }}</strong>,
        Roll No. <strong>{{ $student->roll_number }}</strong>, has left <strong>{{ $school->name }}</strong> as of <strong>{{ date('d-m-Y') }}</strong>.</p>
    <p>He/She was regular in attendance and well-behaved during his/her stay.</p>
    <p>Issued on: <strong>{{ date('d-m-Y') }}</strong></p>
    <div class="signature-block">
        <span>Headmaster/Principal</span>
    </div>
</div>
@endsection 