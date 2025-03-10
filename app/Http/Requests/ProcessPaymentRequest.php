<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'date_range' => ['required', 'string'],
            'selected_entries' => ['required', 'array'],
            'selected_entries.*' => ['required', 'integer', 'exists:time_entries,id'],
            'recipient_id' => ['nullable', 'string'],
        ];
    }
}