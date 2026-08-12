<?php

namespace Tests\Feature\Settings;

use App\Enums\CodigoRol;
use App\Enums\EstadoEvaluacion;
use App\Http\Requests\Evaluation\StoreDescriptorFilesRequest;
use App\Models\Evaluacion;
use App\Models\ModeloEvaluacion;
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

    public function test_administrator_can_update_active_evaluation_schedule(): void
    {
        $administrator = $this->userWithRole(CodigoRol::Administrador);
        $evaluation = $this->evaluation($administrator, EstadoEvaluacion::EnEvaluacion);

        $this->actingAs($administrator)->put(route('admin.evaluations.schedule.update', $evaluation), [
            'evaluation_id' => $evaluation->id,
            'fecha_inicio' => today()->subMonth()->toDateString(),
            'fecha_limite_carga' => today()->subDay()->toDateString(),
            'fecha_inicio_evaluacion' => today()->toDateString(),
            'fecha_cierre_prevista' => today()->addMonth()->toDateString(),
        ])->assertSessionHas('status');

        $this->assertSame(today()->addMonth()->toDateString(), $evaluation->fresh()->fecha_cierre_prevista->toDateString());
        $this->assertDatabaseHas('auditorias', ['accion' => 'EVALUACION_CRONOGRAMA_ACTUALIZADO', 'registro_id' => $evaluation->id]);
    }

    public function test_schedule_cannot_reopen_review_or_be_changed_by_unauthorized_role(): void
    {
        $administrator = $this->userWithRole(CodigoRol::Administrador);
        $evaluation = $this->evaluation($administrator, EstadoEvaluacion::EnEvaluacion);
        $payload = [
            'fecha_inicio' => today()->toDateString(),
            'fecha_limite_carga' => today()->addDay()->toDateString(),
            'fecha_inicio_evaluacion' => today()->addDays(2)->toDateString(),
            'fecha_cierre_prevista' => today()->addMonth()->toDateString(),
        ];

        $this->actingAs($administrator)->put(route('admin.evaluations.schedule.update', $evaluation), $payload)
            ->assertSessionHasErrors('fecha_inicio_evaluacion');

        $responsible = $this->userWithRole(CodigoRol::ResponsableDominio);
        $this->actingAs($responsible)->put(route('admin.evaluations.schedule.update', $evaluation), $payload)->assertForbidden();
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

    private function evaluation(User $administrator, EstadoEvaluacion $state): Evaluacion
    {
        return Evaluacion::query()->create([
            'modelo_evaluacion_id' => ModeloEvaluacion::query()->firstOrFail()->id,
            'codigo' => 'EVAL-SCHEDULE-'.str()->random(6),
            'nombre' => 'Evaluación con cronograma editable',
            'tipo_escenario' => 'MIXTA',
            'fecha_inicio' => today()->subMonth(),
            'fecha_limite_carga' => today()->subWeek(),
            'fecha_inicio_evaluacion' => today()->subDays(6),
            'fecha_cierre_prevista' => today()->addWeek(),
            'estado' => $state,
            'creada_por' => $administrator->id,
        ]);
    }
}
