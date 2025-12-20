@extends('layouts.certificate')

@section('content')
<div class="certificate modern-style-5">
    <h1>Leaving Certificate</h1>
    <p>This is to certify that <strong>{{ $student->name }}</strong>, S/O <strong>{{ $student->father_name }}</strong>,
        Roll No. <strong>{{ $student->roll_number }}</strong>, has left <strong>{{ $school->name }}</strong> after successful completion of studies.</p>
    <p>He/She was respectful and maintained discipline throughout his/her stay.</p>
    <p>Issued: <strong>{{ date('d-m-Y') }}</strong></p>
    <div class="signature-block">
        <span>Principal</span>
        <span>School Stamp</span>
    </div>
</div>
@endsection 