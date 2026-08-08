<?php

namespace Tests\Feature\Setup;

use App\Enums\CodigoRol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InitialAdministratorTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_login_redirects_to_initial_setup_when_there_are_no_users(): void
    {
        $this->get(route('login'))->assertRedirect(route('setup.create'));
        $this->get(route('setup.create'))->assertOk()->assertSee('Crear administrador inicial');
    }

    public function test_first_administrator_can_be_created_from_the_web_setup(): void
    {
        $this->post(route('setup.store'), [
            'name' => 'Administrador Inicial',
            'email' => 'admin@example.test',
            'password' => 'Clave-Segura-2026!',
            'password_confirmation' => 'Clave-Segura-2026!',
        ])->assertRedirect(route('login'));

        $user = User::query()->where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue($user->activo);
        $this->assertTrue($user->hasRole(CodigoRol::Administrador));
        $this->assertTrue(Hash::check('Clave-Segura-2026!', $user->password));
        $this->assertDatabaseHas('auditorias', ['accion' => 'ADMINISTRADOR_INICIAL_CREADO', 'registro_id' => $user->id]);
    }

    public function test_setup_is_disabled_after_the_first_user_exists(): void
    {
        User::factory()->create();

        $this->get(route('setup.create'))->assertRedirect(route('login'));
        $this->post(route('setup.store'), [
            'name' => 'Segundo Administrador', 'email' => 'segundo@example.test',
            'password' => 'Clave-Segura-2026!', 'password_confirmation' => 'Clave-Segura-2026!',
        ])->assertForbidden();
        $this->assertDatabaseCount('users', 1);
    }
}
