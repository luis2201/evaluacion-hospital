<?php

namespace Tests\Feature\Admin;

use App\Enums\CodigoRol;
use App\Models\Auditoria;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_non_administrator_cannot_access_user_management(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_administrator_can_create_a_user_with_roles(): void
    {
        $administrator = $this->administrator();
        $role = Role::query()->where('codigo', CodigoRol::EvaluadorExterno->value)->firstOrFail();

        $this->actingAs($administrator)->post(route('admin.users.store'), [
            'name' => 'Evaluador Externo', 'email' => 'evaluador@example.test',
            'password' => 'Clave-Segura-2026!', 'password_confirmation' => 'Clave-Segura-2026!',
            'activo' => '1', 'roles' => [$role->id],
        ])->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'evaluador@example.test')->firstOrFail();
        $this->assertTrue($user->activo);
        $this->assertTrue(Hash::check('Clave-Segura-2026!', $user->password));
        $this->assertTrue($user->hasRole(CodigoRol::EvaluadorExterno));
        $this->assertDatabaseHas('auditorias', ['accion' => 'USUARIO_CREADO', 'registro_id' => $user->id]);
    }

    public function test_user_list_excludes_administrators_and_shows_other_users(): void
    {
        $administrator = $this->administrator();
        $managedUser = User::factory()->create(['email' => 'usuario.gestionado@example.test']);

        $this->actingAs($administrator)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Usuarios')
            ->assertSee($managedUser->email)
            ->assertDontSee($administrator->email);
    }

    public function test_administrator_cannot_deactivate_own_account(): void
    {
        $administrator = $this->administrator();
        $role = Role::query()->where('codigo', CodigoRol::Administrador->value)->firstOrFail();

        $this->actingAs($administrator)->put(route('admin.users.update', $administrator), [
            'name' => $administrator->name, 'email' => $administrator->email,
            'activo' => '0', 'roles' => [$role->id],
        ])->assertSessionHasErrors('activo');

        $this->assertTrue($administrator->fresh()->activo);
    }

    public function test_administrator_can_change_another_users_password_from_edit_form(): void
    {
        $administrator = $this->administrator();
        $managedUser = User::factory()->create(['password' => Hash::make('Clave-Anterior-2026!')]);
        $role = Role::query()->where('codigo', CodigoRol::EvaluadorExterno->value)->firstOrFail();
        $managedUser->roles()->attach($role, ['created_at' => now()]);

        $this->actingAs($administrator)->put(route('admin.users.update', $managedUser), [
            'name' => $managedUser->name,
            'email' => $managedUser->email,
            'password' => 'Clave-Nueva-2026!',
            'password_confirmation' => 'Clave-Nueva-2026!',
            'activo' => '1',
            'roles' => [$role->id],
        ])->assertRedirect(route('admin.users.edit', $managedUser));

        $managedUser->refresh();
        $this->assertFalse(Hash::check('Clave-Anterior-2026!', $managedUser->password));
        $this->assertTrue(Hash::check('Clave-Nueva-2026!', $managedUser->password));
        $this->assertNotNull($managedUser->password_changed_at);

        $audit = Auditoria::query()->where('accion', 'USUARIO_ACTUALIZADO')->where('registro_id', $managedUser->id)->firstOrFail();
        $this->assertArrayNotHasKey('password', $audit->valores_anteriores ?? []);
        $this->assertArrayNotHasKey('password', $audit->valores_nuevos ?? []);
    }

    public function test_administrator_can_change_own_password_from_user_edit_form(): void
    {
        $administrator = $this->administrator();
        $administrator->forceFill(['password' => Hash::make('Clave-Anterior-2026!')])->save();
        $administratorRole = Role::query()->where('codigo', CodigoRol::Administrador->value)->firstOrFail();

        $this->actingAs($administrator)->put(route('admin.users.update', $administrator), [
            'name' => $administrator->name,
            'email' => $administrator->email,
            'password' => 'Clave-Nueva-2026!',
            'password_confirmation' => 'Clave-Nueva-2026!',
            'activo' => '1',
            'roles' => [(string) $administratorRole->id],
        ])->assertRedirect(route('admin.users.edit', $administrator));

        $administrator->refresh();
        $this->assertFalse(Hash::check('Clave-Anterior-2026!', $administrator->password));
        $this->assertTrue(Hash::check('Clave-Nueva-2026!', $administrator->password));
        $this->assertTrue($administrator->isAdministrator());
        $this->assertTrue($administrator->activo);
    }

    private function administrator(): User
    {
        $user = User::factory()->create(['activo' => true]);
        $role = Role::query()->where('codigo', CodigoRol::Administrador->value)->firstOrFail();
        $user->roles()->attach($role, ['created_at' => now()]);

        return $user;
    }
}
