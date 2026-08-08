<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', Rules\Password::defaults()]]);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) use ($audit): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60), 'password_changed_at' => now()])->save();
            $audit->record('RESTABLECIMIENTO_PASSWORD', 'users', $user->id, userId: $user->id);
            event(new PasswordReset($user));
        });

        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('status', 'Contraseña restablecida correctamente.')
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
