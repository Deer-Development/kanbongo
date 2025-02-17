<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;

class ValidateTagUpdate extends FormRequest
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
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:255',
            'container_id' => 'required|integer|exists:containers,id',
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
            'name.string' => 'The name field must be a string.',
            'name.max' => 'The name field must not exceed 255 characters.',
            'color.required' => 'The color field is required.',
            'color.string' => 'The color field must be a string.',
            'color.max' => 'The color field must not exceed 255 characters.',
            'container_id.required' => 'The container_id field is required.',
            'container_id.integer' => 'The container_id field must be an integer.',
            'container_id.exists' => 'The selected container_id is invalid.',
        ];
    }
}
