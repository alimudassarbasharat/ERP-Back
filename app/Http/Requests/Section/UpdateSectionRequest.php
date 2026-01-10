<?php

namespace App\Http\Requests\Section;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSectionRequest extends FormRequest
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
        $sectionId = $this->route('id');
        
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($sectionId) {
                    // Check for unique section name within same merchant (excluding current section)
                    $merchantId = auth()->user()->merchant_id ?? null;
                    $exists = \App\Models\Section::where('name', $value)
                        ->where('merchant_id', $merchantId)
                        ->where('id', '!=', $sectionId)
                        ->exists();
                    
                    if ($exists) {
                        $fail('A section with this name already exists.');
                    }
                },
            ],
            'description' => 'required|string',
            'status' => 'sometimes|in:active,inactive',
            'class_ids' => 'sometimes|array', // Optional: array of class IDs to assign
            'class_ids.*' => 'exists:classes,id', // Each class ID must exist
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
            'name.required' => 'Section name is required',
            'name.unique' => 'This section name already exists',
            'description.required' => 'Section description is required',
            'status.in' => 'Status must be either active or inactive',
            'class_ids.array' => 'Class IDs must be an array',
            'class_ids.*.exists' => 'One or more selected classes do not exist',
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