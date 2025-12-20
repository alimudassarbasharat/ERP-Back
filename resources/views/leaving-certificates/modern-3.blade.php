@extends('layouts.certificate')

@section('content')
<div class="certificate modern-style-3">
    <h1>Leaving Certificate</h1>
    <p>This is to certify that <strong>{{ $student->name }}</strong>, S/O <strong>{{ $student->father_name }}</strong>,
        Roll No. <strong>{{ $student->roll_number }}</strong>, was a bonafide student of <strong>{{ $school->name }}</strong> from <strong>{{ $student->admission_date ?? '_____' }}</strong> to <strong>{{ $student->leaving_date ?? '_____' }}</strong>.</p>
    <p>He/She left the school on <strong>{{ date('d-m-Y') }}</strong> with good moral character.</p>
    <div class="signature-block">
        <span>School Seal</span>
        <span>Principal</span>
    </div>
</div>
@endsection 