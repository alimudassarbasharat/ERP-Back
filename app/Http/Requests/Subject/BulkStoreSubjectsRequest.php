<?php

namespace App\Http\Requests\Subject;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreSubjectsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'names' => 'required|array|min:1',
            'names.*' => 'required|string|max:255',
        ];
    }
}


