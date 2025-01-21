<?php

namespace App\Http\Requests\Board;

use Illuminate\Foundation\Http\FormRequest;

class ValidateBoardStore extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
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
            'color' => 'required|string|max:12',
            'container_id' => 'required|exists:containers,id',
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
            'color.max' => 'The color field must not exceed 12 characters.',
            'container_id.required' => 'The container_id field is required.',
            'container_id.integer' => 'The container_id field must be an integer.',
            'container_id.exists' => 'The selected container id is invalid.',
        ];
    }
}
