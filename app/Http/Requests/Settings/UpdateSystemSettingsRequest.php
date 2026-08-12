<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institution_name' => ['required', 'string', 'max:150'],
            'institution_short_name' => ['required', 'string', 'max:30'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'max_upload_files' => ['required', 'integer', 'min:1', 'max:20'],
            'max_file_size_mb' => ['required', 'integer', 'min:1', 'max:50'],
            'session_lifetime_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'minimum_password_length' => ['required', 'integer', 'min:12', 'max:32'],
            'login_attempts' => ['required', 'integer', 'min:3', 'max:10'],
            'login_lock_seconds' => ['required', 'integer', 'min:30', 'max:900'],
        ];
    }
}
