@extends('layouts.certificate')

@section('content')
<div class="certificate modern-style-4">
    <h1>Leaving Certificate</h1>
    <p>This is to certify that <strong>{{ $student->name }}</strong>, S/O <strong>{{ $student->father_name }}</strong>,
        Roll No. <strong>{{ $student->roll_number }}</strong>, was enrolled at <strong>{{ $school->name }}</strong> for the session <strong>{{ $student->session ?? '_____' }}</strong>.</p>
    <p>He/She has left the school on <strong>{{ date('d-m-Y') }}</strong> with a record of good conduct.</p>
    <div class="signature-block">
        <span>Authorized Signatory</span>
    </div>
</div>
@endsection 