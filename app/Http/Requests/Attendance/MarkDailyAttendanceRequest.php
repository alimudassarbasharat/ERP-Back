<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class MarkDailyAttendanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:present,absent,late,leave,medical,online_present,proxy_suspected',
            'attendances.*.time_in' => 'nullable|date_format:H:i',
            'attendances.*.time_out' => 'nullable|date_format:H:i',
            'attendances.*.remarks' => 'nullable|string|max:500',
        ];
    }
}


