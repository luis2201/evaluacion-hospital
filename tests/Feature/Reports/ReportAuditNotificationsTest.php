<?php

namespace Tests\Feature\Reports;

use App\Actions\CreateEvaluation;
use App\Enums\CodigoRol;
use App\Enums\EstadoEvaluacion;
use App\Models\Evaluacion;
use App\Models\ModeloEvaluacion;
use App\Models\Role;
use App\Models\User;
use App\Services\EvaluationCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAuditNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_authorized_user_downloads_pdf_and_trace_is_recorded(): void
    {
        [$evaluation, $administrator] = $this->evaluation();

        $response = $this->actingAs($administrator)->get(route('evaluations.results.pdf', $evaluation));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertDatabaseHas('reporte_descargas', ['evaluacion_id' => $evaluation->id, 'user_id' => $administrator->id, 'tipo' => 'RESULTADO_GENERAL_PDF']);
        $this->assertDatabaseHas('auditorias', ['accion' => 'REPORTE_RESULTADOS_DESCARGADO', 'registro_id' => $evaluation->id]);
    }

    public function test_auditor_can_filter_log_but_responsible_cannot_access_it(): void
    {
        $auditor = $this->userWithRole(CodigoRol::AuditorLectura);
        $responsible = $this->userWithRole(CodigoRol::ResponsableDominio);

        $this->actingAs($auditor)->get(route('audit.index', ['accion' => 'INICIO_SESION_FALLIDO']))->assertOk()->assertSee('Bitácora de cambios');
        $this->actingAs($responsible)->get(route('audit.index'))->assertForbidden();
    }

    public function test_calendar_transition_sends_internal_notifications(): void
    {
        [$evaluation, , $responsible, $evaluator] = $this->evaluation();
        $evaluation->update(['estado' => EstadoEvaluacion::Borrador]);

        app(EvaluationCalendarService::class)->sync($evaluation);

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $responsible->id]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $evaluator->id]);
    }

    /** @return array{Evaluacion, User, User, User} */
    private function evaluation(): array
    {
        $administrator = $this->userWithRole(CodigoRol::Administrador);
        $responsible = $this->userWithRole(CodigoRol::ResponsableDominio);
        $evaluator = $this->userWithRole(CodigoRol::EvaluadorExterno);
        $model = ModeloEvaluacion::query()->with('dominios')->firstOrFail();
        $evaluation = app(CreateEvaluation::class)->execute([
            'modelo_evaluacion_id' => $model->id, 'codigo' => 'EVAL-PDF-001', 'nombre' => 'Evaluación para reporte PDF',
            'descripcion' => null, 'tipo_escenario' => 'MIXTA', 'fecha_inicio' => today()->subMonth()->toDateString(),
            'fecha_limite_carga' => today()->subWeek()->toDateString(), 'fecha_inicio_evaluacion' => today()->subDays(6)->toDateString(),
            'fecha_cierre_prevista' => today()->addWeek()->toDateString(),
            'responsables' => $model->dominios->mapWithKeys(fn ($domain) => [$domain->id => $responsible->id])->all(),
            'evaluadores' => [$evaluator->id],
        ], $administrator);
        app(EvaluationCalendarService::class)->sync($evaluation);

        return [$evaluation->fresh(), $administrator, $responsible, $evaluator];
    }

    private function userWithRole(CodigoRol $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('codigo', $role->value)->firstOrFail(), ['created_at' => now()]);

        return $user;
    }
}
