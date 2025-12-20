<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'roll_number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('students')->ignore($this->student->id)
            ],
            'class_id' => 'sometimes|required|exists:classes,id',
            'gender' => 'sometimes|required|in:male,female,other',
            'date_of_birth' => 'sometimes|required|date',
            'blood_group' => 'nullable|string|max:10',
            'admission_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|in:active,inactive,graduated',
            'photo' => 'nullable|image|max:2048',
            
            // Contact Information
            'contact_info.email' => 'nullable|email|max:255',
            'contact_info.phone' => 'nullable|string|max:20',
            'contact_info.whatsapp_number' => 'nullable|string|max:20',
            'contact_info.address' => 'nullable|string|max:500',
            
            // Family Information
            'family_info.father_name' => 'nullable|string|max:255',
            'family_info.father_occupation' => 'nullable|string|max:255',
            'family_info.mother_name' => 'nullable|string|max:255',
            'family_info.mother_occupation' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required',
            'first_name.max' => 'First name cannot exceed 255 characters',
            'last_name.required' => 'Last name is required',
            'last_name.max' => 'Last name cannot exceed 255 characters',
            'roll_number.required' => 'Roll number is required',
            'roll_number.unique' => 'This roll number is already taken',
            'roll_number.max' => 'Roll number cannot exceed 50 characters',
            'class_id.required' => 'Class is required',
            'class_id.exists' => 'Selected class is invalid',
            'gender.required' => 'Gender is required',
            'gender.in' => 'Gender must be male, female, or other',
            'date_of_birth.required' => 'Date of birth is required',
            'date_of_birth.date' => 'Invalid date of birth format',
            'blood_group.max' => 'Blood group cannot exceed 10 characters',
            'admission_date.required' => 'Admission date is required',
            'admission_date.date' => 'Invalid admission date format',
            'status.required' => 'Status is required',
            'status.in' => 'Status must be active, inactive, or graduated',
            'photo.image' => 'The file must be an image',
            'photo.max' => 'Image size cannot exceed 2MB',
            
            // Contact Information Messages
            'contact_info.email.email' => 'Invalid email format',
            'contact_info.email.max' => 'Email cannot exceed 255 characters',
            'contact_info.phone.max' => 'Phone number cannot exceed 20 characters',
            'contact_info.whatsapp_number.max' => 'WhatsApp number cannot exceed 20 characters',
            'contact_info.address.max' => 'Address cannot exceed 500 characters',
            
            // Family Information Messages
            'family_info.father_name.max' => 'Father\'s name cannot exceed 255 characters',
            'family_info.father_occupation.max' => 'Father\'s occupation cannot exceed 255 characters',
            'family_info.mother_name.max' => 'Mother\'s name cannot exceed 255 characters',
            'family_info.mother_occupation.max' => 'Mother\'s occupation cannot exceed 255 characters',
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