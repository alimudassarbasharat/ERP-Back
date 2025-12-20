@extends('layouts.certificate')

@section('content')
<div class="certificate modern-style-7">
    <h1>Character Certificate</h1>
    <p>This is to certify that <strong>{{ $student->name }}</strong>, S/O <strong>{{ $student->father_name }}</strong>,
        Roll No. <strong>{{ $student->roll_number }}</strong>, was a student of <strong>{{ $school->name }}</strong> and maintained a good academic and moral record.</p>
    <p>He/She is recommended for further studies.</p>
    <p>Date: <strong>{{ date('d-m-Y') }}</strong></p>
    <div class="signature-block">
        <span>Principal</span>
        <span>School Stamp</span>
    </div>
</div>
@endsection 