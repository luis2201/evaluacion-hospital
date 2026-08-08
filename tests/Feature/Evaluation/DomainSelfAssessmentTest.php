<?php

namespace Tests\Feature\Evaluation;

use App\Actions\CreateEvaluation;
use App\Enums\CodigoRol;
use App\Enums\EstadoAutoevaluacion;
use App\Enums\EstadoEvaluacion;
use App\Enums\EstadoEvaluacionDominio;
use App\Models\AutoevaluacionDominio;
use App\Models\Evaluacion;
use App\Models\EvaluacionDominio;
use App\Models\ModeloEvaluacion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainSelfAssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_assigned_responsible_can_save_a_draft(): void
    {
        [$evaluation, $domain, $responsible] = $this->evaluationInEvidenceLoading();

        $this->actingAs($responsible)->post($this->route($evaluation, $domain->id), [
            'contenido' => 'Análisis preliminar del dominio.',
            'estado' => EstadoAutoevaluacion::Borrador->value,
        ])->assertSessionHas('status');

        $selfAssessment = AutoevaluacionDominio::query()->firstOrFail();
        $this->assertSame(EstadoAutoevaluacion::Borrador, $selfAssessment->estado);
        $this->assertSame(4, $selfAssessment->cantidad_palabras);
        $this->assertSame(EstadoEvaluacionDominio::EnCarga, $domain->fresh()->estado);
        $this->assertDatabaseHas('auditorias', ['accion' => 'AUTOEVALUACION_GUARDADA', 'registro_id' => $selfAssessment->id]);
    }

    public function test_sending_is_final_and_updates_the_domain(): void
    {
        [$evaluation, $domain, $responsible] = $this->evaluationInEvidenceLoading();

        $this->actingAs($responsible)->post($this->route($evaluation, $domain->id), [
            'contenido' => 'Valoración definitiva del dominio.',
            'estado' => EstadoAutoevaluacion::Enviada->value,
        ])->assertSessionHas('status');

        $selfAssessment = AutoevaluacionDominio::query()->firstOrFail();
        $this->assertSame(EstadoAutoevaluacion::Enviada, $selfAssessment->estado);
        $this->assertNotNull($selfAssessment->enviada_at);
        $domain->refresh();
        $this->assertSame(EstadoEvaluacionDominio::Enviado, $domain->estado);
        $this->assertNotNull($domain->enviado_at);
        $this->assertDatabaseHas('auditorias', ['accion' => 'AUTOEVALUACION_ENVIADA', 'registro_id' => $selfAssessment->id]);

        $this->post($this->route($evaluation, $domain->id), [
            'contenido' => 'Intento de modificación.',
            'estado' => EstadoAutoevaluacion::Borrador->value,
        ])->assertSessionHasErrors('autoevaluacion');

        $this->assertSame('Valoración definitiva del dominio.', $selfAssessment->fresh()->contenido);
    }

    public function test_content_cannot_exceed_250_words(): void
    {
        [$evaluation, $domain, $responsible] = $this->evaluationInEvidenceLoading();

        $this->actingAs($responsible)->post($this->route($evaluation, $domain->id), [
            'contenido' => implode(' ', array_fill(0, 251, 'palabra')),
            'estado' => EstadoAutoevaluacion::Borrador->value,
        ])->assertSessionHasErrors('contenido');

        $this->assertDatabaseCount('autoevaluaciones_dominios', 0);
    }

    public function test_responsible_cannot_edit_another_domain(): void
    {
        [$evaluation, $domain, $responsible] = $this->evaluationInEvidenceLoading();
        $otherResponsible = $this->userWithRole(CodigoRol::ResponsableDominio);
        $domain->update(['responsable_id' => $otherResponsible->id]);

        $this->actingAs($responsible)->post($this->route($evaluation, $domain->id), [
            'contenido' => 'Contenido no autorizado.',
            'estado' => EstadoAutoevaluacion::Borrador->value,
        ])->assertForbidden();

        $this->assertDatabaseCount('autoevaluaciones_dominios', 0);
    }

    public function test_self_assessment_cannot_be_saved_outside_evidence_loading(): void
    {
        [$evaluation, $domain, $responsible] = $this->evaluationInEvidenceLoading();
        $evaluation->update(['estado' => EstadoEvaluacion::EnEvaluacion]);

        $this->actingAs($responsible)->post($this->route($evaluation, $domain->id), [
            'contenido' => 'Contenido fuera de la fase permitida.',
            'estado' => EstadoAutoevaluacion::Borrador->value,
        ])->assertSessionHasErrors('autoevaluacion');

        $this->assertDatabaseCount('autoevaluaciones_dominios', 0);
    }

    public function test_evaluator_can_read_a_submitted_self_assessment_but_cannot_edit_it(): void
    {
        [$evaluation, $domain, $responsible, $evaluator] = $this->evaluationInEvidenceLoading();
        $content = 'Contenido institucional visible para el evaluador asignado.';

        $this->actingAs($responsible)->post($this->route($evaluation, $domain->id), [
            'contenido' => $content,
            'estado' => EstadoAutoevaluacion::Enviada->value,
        ]);

        $this->actingAs($evaluator)
            ->get(route('evaluations.show', ['evaluacion' => $evaluation, 'dominio' => $domain->id]))
            ->assertOk()
            ->assertSee($content)
            ->assertSee('Autoevaluación del dominio')
            ->assertDontSee('Ingreso por dominio')
            ->assertDontSee('Consulta general');

        $this->post($this->route($evaluation, $domain->id), [
            'contenido' => 'Modificación del evaluador.',
            'estado' => EstadoAutoevaluacion::Borrador->value,
        ])->assertForbidden();
    }

    /** @return array{Evaluacion, EvaluacionDominio, User, User} */
    private function evaluationInEvidenceLoading(): array
    {
        $administrator = $this->userWithRole(CodigoRol::Administrador);
        $responsible = $this->userWithRole(CodigoRol::ResponsableDominio);
        $evaluator = $this->userWithRole(CodigoRol::EvaluadorExterno);
        $model = ModeloEvaluacion::query()->with('dominios')->firstOrFail();
        $evaluation = app(CreateEvaluation::class)->execute([
            'modelo_evaluacion_id' => $model->id,
            'codigo' => 'EVAL-AUTO-001',
            'nombre' => 'Evaluación para autoevaluación',
            'descripcion' => null,
            'tipo_escenario' => 'MIXTA',
            'fecha_inicio' => '2026-08-01',
            'fecha_limite_carga' => '2026-08-31',
            'fecha_inicio_evaluacion' => '2026-09-01',
            'fecha_cierre_prevista' => '2026-09-30',
            'responsables' => $model->dominios->mapWithKeys(fn ($item) => [$item->id => $responsible->id])->all(),
            'evaluadores' => [$evaluator->id],
        ], $administrator);

        $this->actingAs($administrator)->post(route('admin.evaluations.start', $evaluation))->assertSessionHas('status');

        return [$evaluation->fresh(), $evaluation->dominios()->firstOrFail(), $responsible, $evaluator];
    }

    private function userWithRole(CodigoRol $roleCode): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('codigo', $roleCode->value)->firstOrFail();
        $user->roles()->attach($role, ['created_at' => now()]);

        return $user;
    }

    private function route(Evaluacion $evaluation, int $domainId): string
    {
        return route('evaluations.domains.autoevaluation.store', [$evaluation, $domainId]);
    }
}
