<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuditService $audit): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        $request->user()->forceFill(['ultimo_acceso_at' => now()])->save();
        $audit->record('INICIO_SESION', 'users', $request->user()->id);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, AuditService $audit): RedirectResponse
    {
        $userId = $request->user()->id;
        $audit->record('CIERRE_SESION', 'users', $userId);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sesión cerrada correctamente.');
    }
}
