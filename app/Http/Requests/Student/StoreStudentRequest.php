<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreStudentRequest extends FormRequest
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
            // Student main fields
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'roll_number' => 'required|string|max:50|unique:students,roll_number',
            // 'class_id' => 'required|exists:classes,id',
            'gender' => 'required|in:Male,Female,Other',
            'cnic_number' => 'nullable|string|max:25',
            'date_of_birth' => 'required|date',
            'admission_date' => 'required|date',
            'religion' => 'nullable|string|max:50',
            'cast' => 'nullable|string|max:50',
            'blood_group' => 'nullable|string|max:10',
            'photo_path' => 'nullable|string',
            // 'status' => 'required|in:active,inactive,graduated',
            // Contact Information
            'contact_info.reporting_number' => 'nullable|string|max:20',
            'contact_info.whatsapp_number' => 'nullable|string|max:20',
            'contact_info.email' => 'nullable|email|max:255',
            'contact_info.address' => 'nullable|string|max:500',
            'contact_info.province' => 'nullable|string|max:50',
            'contact_info.district' => 'nullable|string|max:50',
            'contact_info.city' => 'nullable|string|max:50',
            'contact_info.village' => 'nullable|string|max:50',
            'contact_info.postal_code' => 'nullable|string|max:20',
            // Family Information
            'family_info.father_name' => 'nullable|string|max:255',
            'family_info.father_cnic' => 'nullable|string|max:25',
            'family_info.father_occupation' => 'nullable|string|max:255',
            'family_info.mother_name' => 'nullable|string|max:255',
            'family_info.mother_cnic' => 'nullable|string|max:25',
            'family_info.mother_occupation' => 'nullable|string|max:255',
            'family_info.guardian_name' => 'nullable|string|max:255',
            'family_info.guardian_cnic' => 'nullable|string|max:25',
            'family_info.guardian_occupation' => 'nullable|string|max:255',
            'family_info.guardian_relationship' => 'nullable|string|max:100',
            'family_info.home_address' => 'nullable|string|max:500',
            'family_info.emergency_contact' => 'nullable|string|max:20',
            'family_info.monthly_income' => 'nullable|numeric',
            'family_info.family_members' => 'nullable|integer',
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
            'cnic_number.max' => 'CNIC number cannot exceed 25 characters',
            'date_of_birth.required' => 'Date of birth is required',
            'date_of_birth.date' => 'Invalid date of birth format',
            'admission_date.date' => 'Invalid admission date format',
            'religion.max' => 'Religion cannot exceed 50 characters',
            'cast.max' => 'Cast cannot exceed 50 characters',
            'blood_group.max' => 'Blood group cannot exceed 10 characters',
            'photo_path.max' => 'Photo path cannot exceed 255 characters',
            'status.required' => 'Status is required',
            'status.in' => 'Status must be active, inactive, or graduated',
            
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
            'family_info.guardian_name.max' => 'Guardian\'s name cannot exceed 255 characters',
            'family_info.guardian_occupation.max' => 'Guardian\'s occupation cannot exceed 255 characters',
            'family_info.guardian_relationship.max' => 'Guardian\'s relationship cannot exceed 100 characters',
            'family_info.home_address.max' => 'Home address cannot exceed 500 characters',
            'family_info.emergency_contact.max' => 'Emergency contact cannot exceed 20 characters',
            'family_info.monthly_income.numeric' => 'Monthly income must be a numeric value',
            'family_info.family_members.integer' => 'Family members must be an integer',
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