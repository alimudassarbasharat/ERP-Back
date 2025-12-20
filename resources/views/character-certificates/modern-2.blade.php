@extends('layouts.certificate')

@section('content')
<div class="certificate modern-style-2">
    <h1>Character Certificate</h1>
    <p>This is to certify that <strong>{{ $student->name }}</strong>, S/O <strong>{{ $student->father_name }}</strong>,
        Roll No. <strong>{{ $student->roll_number }}</strong>, was enrolled at <strong>{{ $school->name }}</strong>.</p>
    <p>During his/her tenure, he/she maintained excellent discipline and moral character.</p>
    <p>Issued on: <strong>{{ date('d-m-Y') }}</strong></p>
    <div class="signature-block">
        <span>Headmaster/Principal</span>
    </div>
</div>
@endsection 