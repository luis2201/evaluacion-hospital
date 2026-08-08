<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CodigoRol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);
        $users = User::query()
            ->with('roles')
            ->whereDoesntHave('roles', fn ($query) => $query->where('codigo', CodigoRol::Administrador->value))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.addcslashes($request->string('search'), '%_').'%';
                $query->where(fn ($query) => $query->where('name', 'like', $search)->orWhere('email', 'like', $search));
            })->latest()->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', ['roles' => Role::query()->orderBy('nombre')->get()]);
    }

    public function store(StoreUserRequest $request, AuditService $audit): RedirectResponse
    {
        $validated = $request->validated();
        $user = DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'name' => $validated['name'], 'email' => mb_strtolower($validated['email']),
                'password' => Hash::make($validated['password']), 'activo' => $validated['activo'],
                'password_changed_at' => now(),
            ]);
            $user->roles()->syncWithPivotValues($validated['roles'], ['created_at' => now()]);

            return $user;
        });
        $audit->recordModel('USUARIO_CREADO', $user->load('roles'));

        return redirect()->route('admin.users.index')->with('status', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', ['managedUser' => $user->load('roles'), 'roles' => Role::query()->orderBy('nombre')->get(), 'sessions' => DB::table('sessions')->where('user_id', $user->id)->latest('last_activity')->get()]);
    }

    public function update(UpdateUserRequest $request, User $user, AuditService $audit): RedirectResponse
    {
        $validated = $request->validated();
        $this->protectLastAdministrator($request, $user, $validated);
        $before = $user->load('roles')->toArray();

        DB::transaction(function () use ($user, $validated): void {
            $user->fill(['name' => $validated['name'], 'email' => mb_strtolower($validated['email']), 'activo' => $validated['activo']]);
            if (! empty($validated['password'])) {
                $user->forceFill(['password' => Hash::make($validated['password']), 'password_changed_at' => now()]);
            }
            $user->save();
            $user->roles()->syncWithPivotValues($validated['roles'], ['created_at' => now()]);
            if (! $user->activo) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        });

        $audit->recordModel('USUARIO_ACTUALIZADO', $user->fresh('roles'), $before);

        return redirect()->route('admin.users.edit', $user)->with('status', 'Usuario actualizado correctamente.');
    }

    /** @param array<string, mixed> $validated */
    private function protectLastAdministrator(Request $request, User $user, array $validated): void
    {
        $administratorRoleId = Role::query()->where('codigo', CodigoRol::Administrador->value)->value('id');
        $selectedRoleIds = collect($validated['roles'])->map(fn (mixed $roleId): int => (int) $roleId);
        $keepsAdminRole = $selectedRoleIds->contains((int) $administratorRoleId);

        if ($request->user()->is($user) && (! $validated['activo'] || ! $keepsAdminRole)) {
            throw ValidationException::withMessages(['activo' => 'No puedes desactivar tu propia cuenta ni retirar tu rol de administrador.']);
        }

        if ($user->isAdministrator() && (! $validated['activo'] || ! $keepsAdminRole)) {
            $otherActiveAdmins = User::query()->whereKeyNot($user->id)->where('activo', true)->whereHas('roles', fn ($query) => $query->where('codigo', CodigoRol::Administrador->value))->exists();
            if (! $otherActiveAdmins) {
                throw ValidationException::withMessages(['roles' => 'El sistema debe conservar al menos un administrador activo.']);
            }
        }
    }
}
