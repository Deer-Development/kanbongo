<?php

namespace App\Http\Requests\Container;

use Illuminate\Foundation\Http\FormRequest;

class ValidateContainerStore extends FormRequest
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
            'is_active' => 'required|boolean',
            'project_id' => 'required|exists:projects,id',
            'members' => 'array',
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
            'is_active.required' => 'The is active field is required.',
            'is_active.boolean' => 'The is active field must be a boolean.',
            'project_id.required' => 'The project id field is required.',
            'project_id.exists' => 'The selected project id is invalid.',
            'members.array' => 'The members field must be an array.',
        ];
    }
}
