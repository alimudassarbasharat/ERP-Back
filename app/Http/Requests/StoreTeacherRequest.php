<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // teachers table (based on migrations)
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'employee_code' => 'required|string|max:255|unique:teachers,employee_code',
            'email' => 'required|email|max:255|unique:teachers,email',
            'password' => 'required|string|min:6',
            // username column may not exist in all schemas, make it nullable if present
            'username' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,on_leave',
            'designation' => 'required|string',
            'department' => 'nullable|integer',
            'qualification' => 'nullable|string',
            // 'specialization' => 'nullable|string',
            'years_of_experience' => 'nullable|integer|min:0',
            'joining_date' => 'required|date',
            'salary' => 'nullable|numeric|min:0',
            'bank_account_details' => 'nullable|string',
            'remarks' => 'nullable|string',

            // teacher_personal_details
            'gender' => 'required|string',
            'date_of_birth' => 'required|date',
            'cnic' => 'required|string|max:20|unique:teacher_personal_details,cnic',
            'religion' => 'nullable|string',
            // 'blood_group' => 'nullable|string',
            'profile_picture' => 'nullable|image|max:2048',

            // teacher_contact_details
            'phone_number' => 'required|string|max:20',
            'address' => 'required|string',
            'province' => 'required|string',
            'city' => 'required|string',
            'emergency_contact' => 'nullable|string'
        ];
    }
}
