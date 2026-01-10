<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolProfileRequest extends FormRequest
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
        $user = auth()->user();
        $school = \App\Models\School::where('merchant_id', $user->merchant_id)->first();
        $schoolId = $school ? $school->id : null;
        
        return [
            // Core Identity
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:10',
                'alpha_num',
                Rule::unique('schools', 'code')
                    ->where('merchant_id', $user->merchant_id)
                    ->ignore($schoolId)
            ],
            'logo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'school_type' => 'nullable|in:school,college,academy',
            'tagline' => 'nullable|string|max:500',
            
            // Contact Information
            'phone_primary' => 'required|string|max:20',
            'phone_secondary' => 'nullable|string|max:20',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('schools', 'email')
                    ->where('merchant_id', $user->merchant_id)
                    ->ignore($schoolId)
            ],
            'website' => 'nullable|url|max:255',
            
            // Address Information
            'country' => 'required|string|max:100',
            'state_province' => 'nullable|string|max:100',
            'city' => 'required|string|max:100',
            'address_line_1' => 'required|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'postal_code' => 'nullable|string|max:20',
            
            // Branding/Theme
            'primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'invoice_footer_text' => 'nullable|string|max:1000',
            'report_header_text' => 'nullable|string|max:1000',
            
            // Academic Defaults
            'timezone' => 'required|string|max:50',
            'currency' => 'required|string|size:3',
            'date_format' => 'required|string|max:20',
            'week_start_day' => 'nullable|in:monday,sunday',
            
            // Communication Settings
            'default_whatsapp_sender' => 'nullable|string|max:20',
            'default_sms_sender' => 'nullable|string|max:20',
            'default_email_sender_name' => 'nullable|string|max:100',
            'notification_channels_enabled' => 'nullable|array',
            'notification_channels_enabled.*' => 'string|in:whatsapp,sms,email'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'School name is required.',
            'code.required' => 'School code is required.',
            'code.unique' => 'This school code is already taken.',
            'code.alpha_num' => 'School code must contain only letters and numbers.',
            'phone_primary.required' => 'Primary phone number is required.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email is already registered.',
            'city.required' => 'City is required.',
            'address_line_1.required' => 'Address line 1 is required.',
            'country.required' => 'Country is required.',
            'timezone.required' => 'Timezone is required.',
            'currency.required' => 'Currency is required.',
            'date_format.required' => 'Date format is required.',
            'logo.image' => 'Logo must be an image file.',
            'logo.max' => 'Logo must not be larger than 2MB.',
            'primary_color.regex' => 'Primary color must be a valid hex color (e.g., #e91e63).',
            'secondary_color.regex' => 'Secondary color must be a valid hex color (e.g., #f8f9fa).',
            'currency.size' => 'Currency must be a 3-letter code (e.g., PKR, USD).',
            'website.url' => 'Website must be a valid URL.'
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'school name',
            'code' => 'school code',
            'phone_primary' => 'primary phone',
            'phone_secondary' => 'secondary phone',
            'address_line_1' => 'address line 1',
            'address_line_2' => 'address line 2',
            'state_province' => 'state/province',
            'postal_code' => 'postal code',
            'primary_color' => 'primary color',
            'secondary_color' => 'secondary color',
            'invoice_footer_text' => 'invoice footer text',
            'report_header_text' => 'report header text',
            'date_format' => 'date format',
            'week_start_day' => 'week start day',
            'default_whatsapp_sender' => 'default WhatsApp sender',
            'default_sms_sender' => 'default SMS sender',
            'default_email_sender_name' => 'default email sender name'
        ];
    }

    /**
     * Prepare the data for validation.
     * Convert JSON string or FormData array to array for notification_channels_enabled
     */
    protected function prepareForValidation(): void
    {
        // Handle FormData array format: notification_channels_enabled[0], notification_channels_enabled[1], etc.
        $channels = [];
        $allInput = $this->all();
        
        foreach ($allInput as $key => $value) {
            if (preg_match('/^notification_channels_enabled\[(\d+)\]$/', $key, $matches)) {
                $channels[(int)$matches[1]] = $value;
            }
        }
        
        if (!empty($channels)) {
            // Sort by index and get values
            ksort($channels);
            $this->merge([
                'notification_channels_enabled' => array_values($channels)
            ]);
        } elseif ($this->has('notification_channels_enabled')) {
            $value = $this->input('notification_channels_enabled');
            
            // If it's already an array, use it
            if (is_array($value)) {
                return;
            }
            
            // If it's a JSON string, decode it
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $this->merge([
                        'notification_channels_enabled' => $decoded
                    ]);
                } elseif (str_starts_with($value, '[') && str_ends_with($value, ']')) {
                    // Try to decode if it looks like JSON
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $this->merge([
                            'notification_channels_enabled' => $decoded
                        ]);
                    }
                }
            }
        }
    }
}