<?php

namespace App\Http\Requests\Total;

use Illuminate\Foundation\Http\FormRequest;

class ValidateTotalStore extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Adjust authorization logic as needed, e.g., use policies or permissions
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // Define validation rules for storing Total resource
            // Example:
            // 'name' => 'required|string|max:255',
        ];
    }

    /**
     * Customize the validation error messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            // Custom error messages (optional)
            // 'name.required' => 'The name field is required.',
        ];
    }
}
