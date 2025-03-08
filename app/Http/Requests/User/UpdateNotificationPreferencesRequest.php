<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activities_enabled' => 'boolean',
            'activities_frequency' => 'in:4_hours,8_hours,daily',
            'member_report_enabled' => 'boolean',
            'owner_report_enabled' => 'boolean',
            'daily_report_time' => 'date_format:H:i:s',
        ];
    }
} 