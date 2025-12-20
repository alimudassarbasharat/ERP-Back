<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'term' => 'sometimes|required|string|max:255',
            'academic_year' => 'sometimes|required|string|max:255',
        ];
    }
}


