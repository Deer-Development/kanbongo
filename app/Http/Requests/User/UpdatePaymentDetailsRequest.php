<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profile_type' => 'required|in:personal,business',
            'full_name' => 'required|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:2',
            'account_holder_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'sort_code' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:50',
            'bic' => 'nullable|string|max:20',
            'routing_number' => 'nullable|string|max:50',
            'bank_code' => 'nullable|string|max:50',
            'business_name' => 'required_if:profile_type,business|string|max:255',
            'business_category' => 'required_if:profile_type,business|string|max:255',
            'business_subcategory' => 'nullable|string|max:255',
            'default_currency' => 'required|string|size:3',
            'supported_currencies' => 'required|array',
            'supported_currencies.*' => 'string|size:3',
            'wise_api_key' => 'nullable|string',
            'wise_profile_id' => 'nullable|string',
            'wise_environment' => 'nullable|in:sandbox,production',
        ];
    }
} 
