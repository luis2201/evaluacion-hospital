<?php

namespace Tests\Feature\Evaluation;

use App\Actions\CreateEvaluation;
use App\Enums\CodigoRol;
use App\Enums\EstadoAutoevaluacion;
use App\Enums\EstadoEvaluacion;
use App\Enums\EstadoEvaluacionDescriptor;
use App\Models\Evaluacion;
use App\Models\ModeloEvaluacion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationResultsAndClosureTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_administrator_can_view_provisional_weighted_results(): void
    {
        [$evaluation, $administrator] = $this->evaluationInReview();

        $this->actingAs($administrator)->get(route('evaluations.results', $evaluation))
            ->assertOk()
            ->assertSee('Resultados de la evaluación')
            ->assertSee('Resultado provisional')
            ->assertSee('Detalle por criterio');
    }

    public function test_incomplete_evaluation_cannot_be_closed(): void
    {
        [$evaluation, $administrator] = $this->evaluationInReview();

        $this->actingAs($administrator)->post(route('admin.evaluations.close', $evaluation))
            ->assertSessionHasErrors('cierre');

        $this->assertSame(EstadoEvaluacion::EnEvaluacion, $evaluation->fresh()->estado);
        $this->assertNull($evaluation->fresh()->cerrada_at);
    }

    public function test_complete_evaluation_is_closed_with_reproducible_official_result(): void
    {
        [$evaluation, $administrator, $responsible, $evaluator] = $this->evaluationInReview();
        $this->completeEvaluation($evaluation, $responsible, $evaluator);

        $this->actingAs($administrator)->post(route('admin.evaluations.close', $evaluation))
            ->assertRedirect(route('evaluations.results', $evaluation))
            ->assertSessionHas('status');

        $closed = $evaluation->fresh();
        $this->assertSame(EstadoEvaluacion::Cerrada, $closed->estado);
        $this->assertSame($administrator->id, $closed->cerrada_por);
        $this->assertNotNull($closed->cerrada_at);
        $this->assertDatabaseHas('vw_resultados_generales', [
            'evaluacion_id' => $evaluation->id,
            'estado_calculo' => 'COMPLETA',
            'categoria_final' => 'Centro de referencia',
        ]);
        $this->assertDatabaseHas('auditorias', ['accion' => 'EVALUACION_CERRADA', 'registro_id' => $evaluation->id]);
        $this->assertSame(5, $evaluation->dominios()->where('estado', 'CERRADO')->count());

        $this->get(route('evaluations.results', $evaluation))->assertOk()->assertSee('Resultado oficial');
    }

    /** @return array{Evaluacion, User, User, User} */
    private function evaluationInReview(): array
    {
        $administrator = $this->userWithRole(CodigoRol::Administrador);
        $responsible = $this->userWithRole(CodigoRol::ResponsableDominio);
        $evaluator = $this->userWithRole(CodigoRol::EvaluadorExterno);
        $model = ModeloEvaluacion::query()->with('dominios')->firstOrFail();
        $evaluation = app(CreateEvaluation::class)->execute([
            'modelo_evaluacion_id' => $model->id,
            'codigo' => 'EVAL-RESULT-001',
            'nombre' => 'Evaluación de resultados',
            'descripcion' => null,
            'tipo_escenario' => 'MIXTA',
            'fecha_inicio' => today()->subMonth()->toDateString(),
            'fecha_limite_carga' => today()->subWeek()->toDateString(),
            'fecha_inicio_evaluacion' => today()->subDays(6)->toDateString(),
            'fecha_cierre_prevista' => today()->addWeek()->toDateString(),
            'responsables' => $model->dominios->mapWithKeys(fn ($domain) => [$domain->id => $responsible->id])->all(),
            'evaluadores' => [$evaluator->id],
        ], $administrator);
        $evaluation->update(['estado' => EstadoEvaluacion::EnEvaluacion]);

        return [$evaluation->fresh(), $administrator, $responsible, $evaluator];
    }

    private function completeEvaluation(Evaluacion $evaluation, User $responsible, User $evaluator): void
    {
        foreach ($evaluation->dominios as $domain) {
            $domain->autoevaluacion()->create([
                'contenido' => 'Autoevaluación institucional enviada.',
                'cantidad_palabras' => 3,
                'estado' => EstadoAutoevaluacion::Enviada,
                'registrada_por' => $responsible->id,
                'enviada_at' => now(),
            ]);
        }

        foreach ($evaluation->descriptores as $descriptor) {
            $descriptor->archivos()->create([
                'disco' => 'local',
                'ruta' => "pruebas/{$descriptor->id}.pdf",
                'nombre_original' => "evidencia-{$descriptor->id}.pdf",
                'nombre_almacenado' => "{$descriptor->id}.pdf",
                'mime_type' => 'application/pdf',
                'extension' => 'pdf',
                'tamano_bytes' => 100,
                'hash_sha256' => hash('sha256', "descriptor-{$descriptor->id}"),
                'cargado_por' => $responsible->id,
            ]);
            $descriptor->update([
                'estado' => EstadoEvaluacionDescriptor::Evaluado,
                'calificacion' => 2,
                'evaluado_por' => $evaluator->id,
                'evaluado_at' => now(),
            ]);
        }
    }

    private function userWithRole(CodigoRol $roleCode): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('codigo', $roleCode->value)->firstOrFail();
        $user->roles()->attach($role, ['created_at' => now()]);

        return $user;
    }
}
