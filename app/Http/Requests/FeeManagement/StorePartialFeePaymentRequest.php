<?php

namespace App\Http\Requests\FeeManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
class StorePartialFeePaymentRequest extends FormRequest
{
    public function authorize()
    {
        // You can add authorization logic here
        return true;
    }

    public function rules()
    {
        return [
            'student_id'    => 'required|integer|exists:students,id',
            'class_id'      => 'required|integer|exists:classes,id',
            'month_for'     => 'required|date',
            'base_fee'      => 'required|numeric',
            'final_amount'  => 'required|numeric',
            'amount_paid'   => 'required|numeric|min:0.01',
            'payment_date'  => 'required|date_format:Y-m-d',
            'payment_mode'  => 'required',
            'remarks'       => 'nullable|string',
            'received_by'   => 'required|integer|exists:users,id',
        ];
    }

    public function messages()
    {
        return [
            'student_id.required'   => 'Student is required.',
            'student_id.exists'     => 'Student does not exist.',
            'class_id.required'     => 'Class is required.',
            'class_id.exists'       => 'Class does not exist.',
            'month_for.required'    => 'Month is required.',
            'base_fee.required'     => 'Base fee is required.',
            'final_amount.required' => 'Final amount is required.',
            'amount_paid.required'  => 'Amount paid is required.',
            'amount_paid.min'       => 'Amount paid must be greater than zero.',
            'payment_date.required' => 'Payment date is required.',
            'payment_mode.required' => 'Payment mode is required.',
            'received_by.required'  => 'Received by is required.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422));
    } 
} 