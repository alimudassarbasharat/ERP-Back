<?php

namespace App\Http\Requests\Session;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $sessionId = $this->route('id') ?? $this->route('session');
        
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('sessions', 'name')
                    ->where('merchant_id', auth()->user()->merchant_id)
                    ->ignore($sessionId)
            ],
            'description' => 'nullable|string|max:1000',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'notes' => 'nullable|string|max:2000',
            'status' => 'nullable|in:draft,active,archived'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Session name is required.',
            'name.unique' => 'A session with this name already exists.',
            'start_date.required' => 'Start date is required.',
            'start_date.date' => 'Start date must be a valid date.',
            'end_date.required' => 'End date is required.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after' => 'End date must be after the start date.',
            'status.in' => 'Status must be draft, active, or archived.'
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'start_date' => 'start date',
            'end_date' => 'end date'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->start_date && $this->end_date) {
                $sessionId = $this->route('id') ?? $this->route('session');
                
                // Check for overlapping sessions (excluding current session)
                $schoolId = \App\Models\School::where('merchant_id', auth()->user()->merchant_id)
                    ->value('id');
                
                if ($schoolId) {
                    $overlapping = \App\Models\Session::where('school_id', $schoolId)
                        ->where('id', '!=', $sessionId)
                        ->where(function ($query) {
                            $query->whereBetween('start_date', [$this->start_date, $this->end_date])
                                ->orWhereBetween('end_date', [$this->start_date, $this->end_date])
                                ->orWhere(function ($innerQuery) {
                                    $innerQuery->where('start_date', '<=', $this->start_date)
                                        ->where('end_date', '>=', $this->end_date);
                                });
                        })
                        ->exists();
                    
                    if ($overlapping) {
                        $validator->errors()->add('start_date', 'Session dates overlap with an existing session.');
                        $validator->errors()->add('end_date', 'Session dates overlap with an existing session.');
                    }
                }
            }
        });
    }
}