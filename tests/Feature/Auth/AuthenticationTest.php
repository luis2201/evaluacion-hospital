<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_active_user_can_log_in_and_the_event_is_audited(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Clave-Segura-2026!'), 'activo' => true]);

        $response = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Clave-Segura-2026!']);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('auditorias', ['user_id' => $user->id, 'accion' => 'INICIO_SESION']);
        $this->assertNotNull($user->fresh()->ultimo_acceso_at);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Clave-Segura-2026!'), 'activo' => false]);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Clave-Segura-2026!'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseHas('auditorias', ['user_id' => $user->id, 'accion' => 'CIERRE_SESION']);
    }

    public function test_password_reset_request_does_not_reveal_if_an_email_exists(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');
        $this->post(route('password.email'), ['email' => 'desconocido@example.test'])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
    }
}
