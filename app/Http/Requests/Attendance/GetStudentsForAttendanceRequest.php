<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class GetStudentsForAttendanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'date' => 'required|date',
            'attendance_mode' => 'required|in:daily,period_wise',
            'subject_id' => 'required_if:attendance_mode,period_wise|exists:subjects,id',
            'period_number' => 'required_if:attendance_mode,period_wise|integer|min:1|max:8',
        ];
    }
}


