<?php

namespace Tests\Feature\Evaluation;

use App\Actions\CreateEvaluation;
use App\Enums\CodigoRol;
use App\Enums\EstadoAutoevaluacion;
use App\Enums\EstadoEvaluacion;
use App\Enums\EstadoEvaluacionDescriptor;
use App\Enums\EstadoObservacion;
use App\Models\Evaluacion;
use App\Models\EvaluacionDescriptor;
use App\Models\ModeloEvaluacion;
use App\Models\Observacion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DescriptorReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_review_cannot_start_before_configured_date(): void
    {
        [$evaluation, $administrator] = $this->evaluationInLoading();

        $this->actingAs($administrator)->post(route('admin.evaluations.review.start', $evaluation))->assertSessionHasErrors('estado');
        $this->assertSame(EstadoEvaluacion::CargaEvidencias, $evaluation->fresh()->estado);
    }

    public function test_administrator_can_start_review_after_all_self_assessments_are_submitted(): void
    {
        [$evaluation, $administrator] = $this->evaluationInLoading();
        $this->submitAllSelfAssessments($evaluation);
        $this->advanceCalendarToReview($evaluation);

        $this->actingAs($administrator)->post(route('admin.evaluations.review.start', $evaluation))->assertSessionHas('status');

        $this->assertSame(EstadoEvaluacion::EnEvaluacion, $evaluation->fresh()->estado);
        $this->assertSame(44, $evaluation->descriptores()->where('estado', EstadoEvaluacionDescriptor::EnEvaluacion->value)->count());
        $this->assertDatabaseHas('auditorias', ['accion' => 'EVALUACION_REVISION_INICIADA', 'registro_id' => $evaluation->id]);

        $evaluator = $evaluation->evaluadores()->firstOrFail();
        $this->actingAs($evaluator)
            ->get(route('evaluations.show', ['evaluacion' => $evaluation, 'seccion' => 'consulta']))
            ->assertOk()
            ->assertSee('Bandeja de revisión')
            ->assertDontSee('Ingreso por dominio')
            ->assertDontSee('Consulta general');
    }

    public function test_assigned_evaluator_can_grade_descriptor_with_evidence_and_history(): void
    {
        Storage::fake('local');
        [$evaluation, , $responsible, $evaluator, , $descriptor] = $this->reviewReadyEvaluation(withEvidence: true);

        $this->actingAs($evaluator)->post(route('evaluations.descriptors.review.grade', [$evaluation, $descriptor]), [
            'calificacion' => 2,
            'observacion_evaluador' => 'La evidencia cumple el descriptor.',
        ])->assertSessionHas('status');

        $descriptor->refresh();
        $this->assertSame(EstadoEvaluacionDescriptor::Evaluado, $descriptor->estado);
        $this->assertSame(2, $descriptor->calificacion->value);
        $this->assertSame($evaluator->id, $descriptor->evaluado_por);
        $this->assertDatabaseHas('evaluacion_descriptor_calificaciones', ['evaluacion_descriptor_id' => $descriptor->id, 'calificacion_nueva' => 2, 'calificada_por' => $evaluator->id]);
        $this->assertDatabaseHas('auditorias', ['accion' => 'DESCRIPTOR_CALIFICADO', 'registro_id' => $descriptor->id]);
    }

    public function test_descriptor_without_file_cannot_be_graded_and_unassigned_evaluator_is_forbidden(): void
    {
        [$evaluation, , , $evaluator, , $descriptor] = $this->reviewReadyEvaluation();

        $this->actingAs($evaluator)->post(route('evaluations.descriptors.review.grade', [$evaluation, $descriptor]), [
            'calificacion' => 1,
        ])->assertSessionHasErrors('calificacion');

        $unassigned = $this->userWithRole(CodigoRol::EvaluadorExterno);
        $this->actingAs($unassigned)->post(route('evaluations.descriptors.review.grade', [$evaluation, $descriptor]), [
            'calificacion' => 1,
        ])->assertForbidden();
        $this->assertNull($descriptor->fresh()->calificacion);
    }

    public function test_observation_remediation_response_close_and_rating_flow(): void
    {
        Storage::fake('local');
        [$evaluation, , $responsible, $evaluator, , $descriptor] = $this->reviewReadyEvaluation();

        $this->actingAs($evaluator)->post(route('evaluations.descriptors.observations.store', [$evaluation, $descriptor]), [
            'asunto' => 'Documento faltante',
            'detalle' => 'Adjunte el protocolo institucional vigente.',
            'fecha_limite' => now()->addWeek()->toDateString(),
        ])->assertSessionHas('status');

        $observation = Observacion::query()->firstOrFail();
        $this->assertSame(EstadoObservacion::Abierta, $observation->estado);
        $this->assertSame(EstadoEvaluacionDescriptor::Observado, $descriptor->fresh()->estado);

        $this->actingAs($responsible)->post(route('evaluations.descriptors.files.store', [$evaluation, $descriptor]), [
            'archivos' => [$this->pdf('protocolo.pdf')],
        ])->assertSessionHas('status');
        $this->post(route('evaluations.descriptors.observations.respond', [$evaluation, $descriptor, $observation]), [
            'respuesta' => 'Se adjuntó el protocolo solicitado.',
        ])->assertSessionHas('status');
        $this->assertSame(EstadoObservacion::Respondida, $observation->fresh()->estado);

        $this->actingAs($evaluator)->post(route('evaluations.descriptors.observations.close', [$evaluation, $descriptor, $observation]))->assertSessionHas('status');
        $this->assertSame(EstadoObservacion::Cerrada, $observation->fresh()->estado);
        $this->assertSame(EstadoEvaluacionDescriptor::EnEvaluacion, $descriptor->fresh()->estado);

        $this->post(route('evaluations.descriptors.review.grade', [$evaluation, $descriptor]), ['calificacion' => 1])->assertSessionHas('status');
        $this->assertSame(1, $descriptor->fresh()->calificacion->value);
    }

    public function test_responsible_cannot_respond_to_observation_from_another_domain(): void
    {
        [$evaluation, , $responsible, $evaluator, $otherResponsible, , $otherDescriptor] = $this->reviewReadyEvaluation();
        $this->actingAs($evaluator)->post(route('evaluations.descriptors.observations.store', [$evaluation, $otherDescriptor]), [
            'asunto' => 'Revisión requerida', 'detalle' => 'Complete la evidencia.',
        ]);
        $observation = Observacion::query()->firstOrFail();

        $this->actingAs($responsible)->post(route('evaluations.descriptors.observations.respond', [$evaluation, $otherDescriptor, $observation]), [
            'respuesta' => 'Intento no autorizado.',
        ])->assertForbidden();
        $this->actingAs($otherResponsible)->post(route('evaluations.descriptors.observations.respond', [$evaluation, $otherDescriptor, $observation]), [
            'respuesta' => 'Respuesta del responsable correcto.',
        ])->assertSessionHas('status');
    }

    /** @return array{Evaluacion, User, User, User, User, EvaluacionDescriptor, EvaluacionDescriptor} */
    private function reviewReadyEvaluation(bool $withEvidence = false): array
    {
        [$evaluation, $administrator, $responsible, $evaluator, $otherResponsible, $descriptor, $otherDescriptor] = $this->evaluationInLoading();
        if ($withEvidence) {
            Storage::fake('local');
            $this->actingAs($responsible)->post(route('evaluations.descriptors.files.store', [$evaluation, $descriptor]), ['archivos' => [$this->pdf('evidencia.pdf')]])->assertSessionHas('status');
        }
        $this->submitAllSelfAssessments($evaluation);
        $this->advanceCalendarToReview($evaluation);
        $this->actingAs($administrator)->post(route('admin.evaluations.review.start', $evaluation))->assertSessionHas('status');

        return [$evaluation->fresh(), $administrator, $responsible, $evaluator, $otherResponsible, $descriptor->fresh(), $otherDescriptor->fresh()];
    }

    /** @return array{Evaluacion, User, User, User, User, EvaluacionDescriptor, EvaluacionDescriptor} */
    private function evaluationInLoading(): array
    {
        $administrator = $this->userWithRole(CodigoRol::Administrador);
        $responsible = $this->userWithRole(CodigoRol::ResponsableDominio);
        $otherResponsible = $this->userWithRole(CodigoRol::ResponsableDominio);
        $evaluator = $this->userWithRole(CodigoRol::EvaluadorExterno);
        $model = ModeloEvaluacion::query()->with('dominios')->firstOrFail();
        $evaluation = app(CreateEvaluation::class)->execute([
            'modelo_evaluacion_id' => $model->id, 'codigo' => 'EVAL-REV-001', 'nombre' => 'Evaluación en revisión',
            'descripcion' => null, 'tipo_escenario' => 'MIXTA', 'fecha_inicio' => '2026-08-01',
            'fecha_limite_carga' => '2026-08-31', 'fecha_inicio_evaluacion' => '2026-09-01', 'fecha_cierre_prevista' => '2026-09-30',
            'responsables' => $model->dominios->mapWithKeys(fn ($domain, $index) => [$domain->id => $index === 0 ? $responsible->id : $otherResponsible->id])->all(),
            'evaluadores' => [$evaluator->id],
        ], $administrator);
        $this->actingAs($administrator)->post(route('admin.evaluations.start', $evaluation));
        $domains = $evaluation->dominios()->orderBy('id')->get();
        $descriptor = $this->descriptorForDomain($evaluation, $domains->first()->dominio_id);
        $otherDescriptor = $this->descriptorForDomain($evaluation, $domains->get(1)->dominio_id);

        return [$evaluation->fresh(), $administrator, $responsible, $evaluator, $otherResponsible, $descriptor, $otherDescriptor];
    }

    private function submitAllSelfAssessments(Evaluacion $evaluation): void
    {
        foreach ($evaluation->dominios as $domain) {
            $domain->autoevaluacion()->create([
                'contenido' => 'Autoevaluación enviada para iniciar la revisión.', 'cantidad_palabras' => 7,
                'estado' => EstadoAutoevaluacion::Enviada, 'registrada_por' => $domain->responsable_id, 'enviada_at' => now(),
            ]);
        }
    }

    private function advanceCalendarToReview(Evaluacion $evaluation): void
    {
        $evaluation->update([
            'fecha_limite_carga' => today()->subDay(),
            'fecha_inicio_evaluacion' => today(),
        ]);
        $evaluation->refresh();
    }

    private function descriptorForDomain(Evaluacion $evaluation, int $domainId): EvaluacionDescriptor
    {
        return $evaluation->descriptores()->whereHas('descriptor.criterio', fn ($query) => $query->where('dominio_id', $domainId))->firstOrFail();
    }

    private function userWithRole(CodigoRol $roleCode): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('codigo', $roleCode->value)->firstOrFail();
        $user->roles()->attach($role, ['created_at' => now()]);

        return $user;
    }

    private function pdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\ncontenido institucional");
    }
}
