<?php

namespace App\Http\Controllers\Setup;

use App\Enums\CodigoRol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Setup\StoreInitialAdministratorRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InitialAdministratorController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (User::query()->exists()) {
            return redirect()->route('login');
        }

        return view('setup.initial-administrator');
    }

    public function store(StoreInitialAdministratorRequest $request, AuditService $audit): RedirectResponse
    {
        $validated = $request->validated();

        $administrator = DB::transaction(function () use ($validated): User {
            $role = Role::query()->where('codigo', CodigoRol::Administrador->value)->lockForUpdate()->firstOrFail();

            if (User::query()->exists()) {
                throw ValidationException::withMessages(['email' => 'La configuración inicial ya fue completada.']);
            }

            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => mb_strtolower($validated['email']),
                'password' => Hash::make($validated['password']),
                'activo' => true,
                'password_changed_at' => now(),
            ]);
            $user->roles()->attach($role, ['created_at' => now()]);

            return $user;
        });

        $audit->record('ADMINISTRADOR_INICIAL_CREADO', 'users', $administrator->id, userId: $administrator->id);

        return redirect()->route('login')->with('status', 'Administrador creado correctamente. Ya puedes iniciar sesión.');
    }
}
