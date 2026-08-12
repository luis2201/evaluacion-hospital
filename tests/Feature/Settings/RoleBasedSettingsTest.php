<?php

namespace Tests\Feature\Settings;

use App\Enums\CodigoRol;
use App\Http\Requests\Evaluation\StoreDescriptorFilesRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_each_role_sees_its_own_capability_profile(): void
    {
        foreach ([
            [CodigoRol::Administrador, 'Administrar usuarios'],
            [CodigoRol::ResponsableDominio, 'Registrar autoevaluaciones'],
            [CodigoRol::EvaluadorExterno, 'Revisar evidencias'],
            [CodigoRol::AuditorLectura, 'Consultar resultados'],
        ] as [$role, $capability]) {
            $this->actingAs($this->userWithRole($role))->get(route('settings.show'))
                ->assertOk()
                ->assertSee($capability);
        }
    }

    public function test_only_administrator_sees_and_updates_system_parameters(): void
    {
        $administrator = $this->userWithRole(CodigoRol::Administrador);
        $this->actingAs($administrator)->get(route('settings.show'))->assertOk()->assertSee('Gestión documental');
        $this->put(route('admin.settings.update'), $this->settingsPayload())->assertSessionHas('status');

        $this->assertDatabaseHas('configuraciones_sistema', ['clave' => 'max_upload_files', 'valor' => '2', 'actualizada_por' => $administrator->id]);
        $this->assertDatabaseHas('auditorias', ['accion' => 'CONFIGURACION_SISTEMA_ACTUALIZADA', 'user_id' => $administrator->id]);
        $rules = (new StoreDescriptorFilesRequest)->rules();
        $this->assertContains('max:2', $rules['archivos']);
        $this->assertContains('max:1024', $rules['archivos.*']);
    }

    public function test_non_administrator_cannot_update_system_parameters(): void
    {
        $responsible = $this->userWithRole(CodigoRol::ResponsableDominio);

        $this->actingAs($responsible)->get(route('settings.show'))->assertOk()->assertDontSee('Gestión documental');
        $this->put(route('admin.settings.update'), $this->settingsPayload())->assertForbidden();
    }

    public function test_evaluator_navigation_excludes_instrument_and_direct_access_is_forbidden(): void
    {
        $evaluator = $this->userWithRole(CodigoRol::EvaluadorExterno);

        $this->actingAs($evaluator)->get(route('settings.show'))
            ->assertOk()
            ->assertDontSee('href="'.route('instruments.index').'"', false);
        $this->get(route('instruments.index'))->assertForbidden();
    }

    /** @return array<string, int|string> */
    private function settingsPayload(): array
    {
        return [
            'institution_name' => 'Hospital de Simulación Institucional',
            'institution_short_name' => 'HSI',
            'support_email' => 'soporte@example.test',
            'max_upload_files' => 2,
            'max_file_size_mb' => 1,
            'session_lifetime_minutes' => 90,
            'minimum_password_length' => 14,
            'login_attempts' => 4,
            'login_lock_seconds' => 120,
        ];
    }

    private function userWithRole(CodigoRol $roleCode): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('codigo', $roleCode->value)->firstOrFail();
        $user->roles()->attach($role, ['created_at' => now()]);

        return $user;
    }
}
