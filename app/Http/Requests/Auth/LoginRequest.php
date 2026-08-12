<?php

namespace App\Http\Requests\Auth;

use App\Services\SystemSettingsService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email'], 'password' => ['required', 'string'], 'remember' => ['nullable', 'boolean']];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => Str::lower($this->string('email')), 'password' => $this->string('password'), 'activo' => true], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), app(SystemSettingsService::class)->integer('login_lock_seconds'));
            throw ValidationException::withMessages(['email' => 'Las credenciales proporcionadas no son correctas o la cuenta está desactivada.']);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), app(SystemSettingsService::class)->integer('login_attempts'))) {
            return;
        }

        event(new Lockout($this));
        $seconds = RateLimiter::availableIn($this->throttleKey());
        throw ValidationException::withMessages(['email' => "Demasiados intentos. Intenta nuevamente en {$seconds} segundos."]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
