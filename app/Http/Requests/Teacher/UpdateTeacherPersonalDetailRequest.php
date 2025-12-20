<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTeacherPersonalDetailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $id = $this->route('teacher_personal_detail');
        return [
            'teacher_id' => 'sometimes|exists:teachers,id|unique:teacher_personal_details,teacher_id,' . $id,
            'gender' => 'sometimes|string|in:male,female,other',
            'date_of_birth' => 'sometimes|date',
            'cnic' => 'sometimes|string|unique:teacher_personal_details,cnic,' . $id,
            'religion' => 'sometimes|string',
            'blood_group' => 'sometimes|string',
            'profile_picture' => 'nullable|string',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422));
    }
}
