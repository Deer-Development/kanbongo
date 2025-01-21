<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ValidateUserUpdate extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            'email' => 'sometimes|nullable|string|email|max:255|unique:users,email,' . $this->user->id,
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
