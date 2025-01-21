<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ValidateUserStore extends FormRequest
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
            'email' => 'required|string|email|max:255|unique:users',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
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
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email field must be a valid email address.',
            'email.max' => 'The email field must have a maximum of 255 characters.',
            'email.unique' => 'The email field must be unique.',
            'first_name.string' => 'The first name field must be a string.',
            'first_name.max' => 'The first name field must have a maximum of 255 characters.',
            'last_name.string' => 'The last name field must be a string.',
            'last_name.max' => 'The last name field must have a maximum of 255 characters.',
        ];
    }
}
