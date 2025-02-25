<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

class ValidateCommentStore extends FormRequest
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
            'content' => 'nullable|string',
            'commentable_id' => 'required|integer',
            'commentable_type' => 'required|string',
            'temporary_uploads' => 'nullable|array',
            'mentioned_users' => 'nullable|array',
            'parent_id' => 'nullable|integer',
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
            'content.required' => 'Content is required.',
            'content.string' => 'Content must be a string.',
            'content.max' => 'Content must not be greater than 4000 characters.',
        ];
    }
}
