<?php

namespace Tests\Feature\Evaluation;

use App\Enums\CodigoRol;
use App\Enums\EstadoEvaluacion;
use App\Enums\EstadoEvaluacionDominio;
use App\Models\Evaluacion;
use App\Models\ModeloEvaluacion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_administrator_creates_fully_instantiated_evaluation(): void
    {
        [$administrator, $responsible, $evaluator] = $this->users();
        $model = ModeloEvaluacion::query()->with('dominios')->firstOrFail();

        $this->actingAs($administrator)->post(route('admin.evaluations.store'), $this->payload($model, $responsible, $evaluator))->assertRedirect();

        $evaluation = Evaluacion::query()->firstOrFail();
        $this->assertSame(EstadoEvaluacion::Borrador, $evaluation->estado);
        $this->assertSame(5, $evaluation->dominios()->count());
        $this->assertSame(44, $evaluation->descriptores()->count());
        $this->assertSame(1, $evaluation->evaluadores()->count());
        $this->assertTrue((bool) $evaluation->evaluadores()->firstOrFail()->pivot->es_principal);
        $this->assertDatabaseHas('auditorias', ['accion' => 'EVALUACION_CREADA', 'registro_id' => $evaluation->id]);
    }

    public function test_enabling_evidence_loading_updates_evaluation_and_domains(): void
    {
        [$administrator, $responsible, $evaluator] = $this->users();
        $model = ModeloEvaluacion::query()->with('dominios')->firstOrFail();
        $this->actingAs($administrator)->post(route('admin.evaluations.store'), $this->payload($model, $responsible, $evaluator));
        $evaluation = Evaluacion::query()->firstOrFail();

        $this->post(route('admin.evaluations.start', $evaluation))->assertSessionHas('status');

        $this->assertSame(EstadoEvaluacion::CargaEvidencias, $evaluation->fresh()->estado);
        $this->assertSame(5, $evaluation->dominios()->where('estado', EstadoEvaluacionDominio::EnCarga->value)->count());
    }

    public function test_responsible_can_view_assigned_evaluation_but_unassigned_user_cannot(): void
    {
        [$administrator, $responsible, $evaluator] = $this->users();
        $model = ModeloEvaluacion::query()->with('dominios')->firstOrFail();
        $this->actingAs($administrator)->post(route('admin.evaluations.store'), $this->payload($model, $responsible, $evaluator));
        $evaluation = Evaluacion::query()->firstOrFail();

        $this->actingAs($responsible)->get(route('evaluations.show', $evaluation))->assertOk()->assertSee($evaluation->nombre);
        $this->actingAs(User::factory()->create())->get(route('evaluations.show', $evaluation))->assertForbidden();
    }

    public function test_user_without_responsible_role_cannot_be_assigned(): void
    {
        [$administrator, , $evaluator] = $this->users();
        $invalidResponsible = User::factory()->create();
        $model = ModeloEvaluacion::query()->with('dominios')->firstOrFail();

        $this->actingAs($administrator)->post(route('admin.evaluations.store'), $this->payload($model, $invalidResponsible, $evaluator))
            ->assertSessionHasErrors('responsables');
        $this->assertDatabaseCount('evaluaciones', 0);
    }

    /** @return array{User,User,User} */
    private function users(): array
    {
        $administrator = $this->userWithRole(CodigoRol::Administrador);
        $responsible = $this->userWithRole(CodigoRol::ResponsableDominio);
        $evaluator = $this->userWithRole(CodigoRol::EvaluadorExterno);

        return [$administrator, $responsible, $evaluator];
    }

    private function userWithRole(CodigoRol $code): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('codigo', $code->value)->firstOrFail();
        $user->roles()->attach($role, ['created_at' => now()]);

        return $user;
    }

    /** @return array<string,mixed> */
    private function payload(ModeloEvaluacion $model, User $responsible, User $evaluator): array
    {
        return [
            'modelo_evaluacion_id' => $model->id, 'codigo' => 'EVAL-2026-001', 'nombre' => 'Evaluación institucional 2026',
            'descripcion' => 'Proceso de prueba', 'tipo_escenario' => 'MIXTA', 'fecha_inicio' => '2026-08-01',
            'fecha_limite_carga' => '2026-08-31', 'fecha_inicio_evaluacion' => '2026-09-01', 'fecha_cierre_prevista' => '2026-09-30',
            'responsables' => $model->dominios->mapWithKeys(fn ($domain) => [$domain->id => $responsible->id])->all(), 'evaluadores' => [$evaluator->id],
        ];
    }
}
