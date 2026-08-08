<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('profile.password');
    }

    public function update(Request $request, AuditService $audit): RedirectResponse
    {
        $validated = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::defaults()]]);
        $request->user()->forceFill(['password' => Hash::make($validated['password']), 'password_changed_at' => now()])->save();
        $audit->record('CAMBIO_PASSWORD', 'users', $request->user()->id);

        return back()->with('status', 'Contraseña actualizada correctamente.');
    }
}
