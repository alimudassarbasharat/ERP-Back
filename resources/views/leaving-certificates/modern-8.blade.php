@extends('layouts.certificate')

@section('content')
<div class="certificate modern-style-8">
    <h1>Leaving Certificate</h1>
    <p>This is to certify that <strong>{{ $student->name }}</strong>, S/O <strong>{{ $student->father_name }}</strong>,
        Roll No. <strong>{{ $student->roll_number }}</strong>, has left <strong>{{ $school->name }}</strong> and was always polite, hardworking, and trustworthy.</p>
    <p>Date: <strong>{{ date('d-m-Y') }}</strong></p>
    <div class="signature-block">
        <span>Principal</span>
    </div>
</div>
@endsection 