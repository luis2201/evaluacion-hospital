<?php

namespace Tests\Feature\Evaluation;

use App\Actions\CreateEvaluation;
use App\Enums\CodigoRol;
use App\Enums\EstadoEvaluacion;
use App\Models\DescriptorArchivo;
use App\Models\Evaluacion;
use App\Models\EvaluacionDescriptor;
use App\Models\EvaluacionDominio;
use App\Models\ModeloEvaluacion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvidenceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_internal_sections_organize_assigned_and_general_domains(): void
    {
        [$evaluation, $ownDomain, $otherDomain, $responsible] = $this->evaluationInEvidenceLoading();

        $this->actingAs($responsible)
            ->get(route('evaluations.show', ['evaluacion' => $evaluation, 'seccion' => 'ingreso']))
            ->assertOk()
            ->assertSee('Ingreso por dominio')
            ->assertSee('Ningún archivo seleccionado')
            ->assertSee($ownDomain->dominio->nombre)
            ->assertDontSee('dominio='.$otherDomain->id, false);

        $this->get(route('evaluations.show', ['evaluacion' => $evaluation, 'seccion' => 'consulta']))
            ->assertOk()
            ->assertSee('Consulta general')
            ->assertSee('dominio='.$ownDomain->id, false)
            ->assertSee('dominio='.$otherDomain->id, false);
    }

    public function test_responsible_can_upload_multiple_private_files_to_own_descriptor(): void
    {
        Storage::fake('local');
        [$evaluation, , , $responsible, , $ownDescriptor] = $this->evaluationInEvidenceLoading();

        $response = $this->actingAs($responsible)->post($this->fileStoreRoute($evaluation, $ownDescriptor), [
            'archivos' => [$this->pdf('protocolo.pdf', 'contenido uno'), $this->pdf('informe.pdf', 'contenido dos')],
            'descripcion' => 'Documentos institucionales',
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseCount('descriptor_archivos', 2);
        DescriptorArchivo::query()->each(fn ($file) => Storage::disk('local')->assertExists($file->ruta));
        $this->assertDatabaseHas('auditorias', ['accion' => 'EVIDENCIA_ARCHIVO_CARGADO']);
    }

    public function test_administrator_only_has_general_consultation_and_cannot_manage_evidence_or_reviews(): void
    {
        Storage::fake('local');
        [$evaluation, , , , , $descriptor] = $this->evaluationInEvidenceLoading();
        $administrator = User::query()->whereHas('roles', fn ($query) => $query->where('codigo', CodigoRol::Administrador->value))->firstOrFail();

        $this->actingAs($administrator)
            ->get(route('evaluations.show', ['evaluacion' => $evaluation, 'seccion' => 'ingreso']))
            ->assertOk()
            ->assertSee('Consulta de descriptores y evidencias')
            ->assertDontSee('Ingreso por dominio')
            ->assertDontSee('Revisión del evaluador');

        $this->post($this->fileStoreRoute($evaluation, $descriptor), [
            'archivos' => [$this->pdf('administrador.pdf', 'sin autorización de escritura')],
        ])->assertForbidden();
        $this->post(route('evaluations.descriptors.review.grade', [$evaluation, $descriptor]), ['calificacion' => 2])->assertForbidden();
    }

    public function test_responsible_cannot_upload_to_another_domain_and_evaluator_cannot_upload(): void
    {
        Storage::fake('local');
        [$evaluation, , , $responsible, $evaluator, , $otherDescriptor] = $this->evaluationInEvidenceLoading();

        $payload = ['archivos' => [$this->pdf('evidencia.pdf', 'privado')]];
        $this->actingAs($responsible)->post($this->fileStoreRoute($evaluation, $otherDescriptor), $payload)->assertForbidden();
        $this->actingAs($evaluator)->post($this->fileStoreRoute($evaluation, $otherDescriptor), $payload)->assertForbidden();
        $this->assertDatabaseCount('descriptor_archivos', 0);
    }

    public function test_duplicate_is_rejected_and_soft_deleted_file_can_be_restored(): void
    {
        Storage::fake('local');
        [$evaluation, , , $responsible, , $descriptor] = $this->evaluationInEvidenceLoading();
        $content = 'mismo contenido institucional';

        $this->actingAs($responsible)->post($this->fileStoreRoute($evaluation, $descriptor), ['archivos' => [$this->pdf('uno.pdf', $content)]])->assertSessionHas('status');
        $file = DescriptorArchivo::query()->firstOrFail();

        $this->post($this->fileStoreRoute($evaluation, $descriptor), ['archivos' => [$this->pdf('copia.pdf', $content)]])->assertSessionHasErrors('archivos');
        $this->delete(route('evaluations.descriptors.files.destroy', [$evaluation, $descriptor, $file]))->assertSessionHas('status');
        $this->assertSoftDeleted($file);

        $this->post($this->fileStoreRoute($evaluation, $descriptor), ['archivos' => [$this->pdf('restaurado.pdf', $content)]])->assertSessionHas('status');
        $this->assertDatabaseCount('descriptor_archivos', 1);
        $this->assertFalse($file->fresh()->trashed());
        $this->assertDatabaseHas('auditorias', ['accion' => 'EVIDENCIA_ARCHIVO_RESTAURADO', 'registro_id' => $file->id]);
    }

    public function test_authorized_evaluator_can_preview_and_download_a_file(): void
    {
        Storage::fake('local');
        [$evaluation, , , $responsible, $evaluator, $descriptor] = $this->evaluationInEvidenceLoading();
        $this->actingAs($responsible)->post($this->fileStoreRoute($evaluation, $descriptor), ['archivos' => [$this->pdf('consulta.pdf', 'contenido visible')]])->assertSessionHas('status');
        $file = DescriptorArchivo::query()->firstOrFail();

        $this->actingAs($evaluator)
            ->get(route('evaluations.descriptors.files.preview', [$evaluation, $descriptor, $file]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('evaluations.descriptors.files.download', [$evaluation, $descriptor, $file]))->assertOk();

        $this->assertDatabaseHas('descriptor_archivo_descargas', ['descriptor_archivo_id' => $file->id, 'user_id' => $evaluator->id]);
    }

    public function test_files_and_links_cannot_be_changed_outside_loading_phase(): void
    {
        Storage::fake('local');
        [$evaluation, , , $responsible, , $descriptor] = $this->evaluationInEvidenceLoading();
        $evaluation->update(['estado' => EstadoEvaluacion::EnEvaluacion]);

        $this->actingAs($responsible)->post($this->fileStoreRoute($evaluation, $descriptor), [
            'archivos' => [$this->pdf('fuera-fase.pdf', 'contenido')],
        ])->assertSessionHasErrors('archivos');
        $this->post(route('evaluations.descriptors.links.store', [$evaluation, $descriptor]), [
            'url' => 'https://example.test/evidencia',
        ])->assertSessionHasErrors('url');

        $this->assertDatabaseCount('descriptor_archivos', 0);
        $this->assertDatabaseCount('descriptor_enlaces', 0);
    }

    public function test_expired_loading_date_blocks_upload_even_if_state_has_not_synced_yet(): void
    {
        Storage::fake('local');
        [$evaluation, , , $responsible, , $descriptor] = $this->evaluationInEvidenceLoading();
        $evaluation->update([
            'fecha_limite_carga' => today()->subDay(),
            'fecha_inicio_evaluacion' => today(),
        ]);

        $this->actingAs($responsible)->post($this->fileStoreRoute($evaluation, $descriptor), [
            'archivos' => [$this->pdf('extemporaneo.pdf', 'contenido fuera de plazo')],
        ])->assertSessionHasErrors('archivos');

        $this->assertDatabaseCount('descriptor_archivos', 0);
    }

    public function test_responsible_can_manage_safe_links_and_executable_files_are_rejected(): void
    {
        Storage::fake('local');
        [$evaluation, , , $responsible, , $descriptor] = $this->evaluationInEvidenceLoading();

        $this->actingAs($responsible)->post(route('evaluations.descriptors.links.store', [$evaluation, $descriptor]), [
            'url' => 'https://example.test/documentacion/protocolo',
            'descripcion' => 'Repositorio institucional',
        ])->assertSessionHas('status');

        $link = $descriptor->enlaces()->firstOrFail();
        $this->assertDatabaseHas('auditorias', ['accion' => 'EVIDENCIA_ENLACE_REGISTRADO', 'registro_id' => $link->id]);
        $this->delete(route('evaluations.descriptors.links.destroy', [$evaluation, $descriptor, $link]))->assertSessionHas('status');
        $this->assertSoftDeleted($link);

        $this->post($this->fileStoreRoute($evaluation, $descriptor), [
            'archivos' => [UploadedFile::fake()->createWithContent('script.php', '<?php echo "riesgo";')],
        ])->assertSessionHasErrors('archivos.0');
        $this->assertDatabaseCount('descriptor_archivos', 0);
    }

    /** @return array{Evaluacion, EvaluacionDominio, EvaluacionDominio, User, User, EvaluacionDescriptor, EvaluacionDescriptor} */
    private function evaluationInEvidenceLoading(): array
    {
        $administrator = $this->userWithRole(CodigoRol::Administrador);
        $responsible = $this->userWithRole(CodigoRol::ResponsableDominio);
        $otherResponsible = $this->userWithRole(CodigoRol::ResponsableDominio);
        $evaluator = $this->userWithRole(CodigoRol::EvaluadorExterno);
        $model = ModeloEvaluacion::query()->with('dominios')->firstOrFail();
        $evaluation = app(CreateEvaluation::class)->execute([
            'modelo_evaluacion_id' => $model->id,
            'codigo' => 'EVAL-EVID-001',
            'nombre' => 'Evaluación documental',
            'descripcion' => null,
            'tipo_escenario' => 'MIXTA',
            'fecha_inicio' => '2026-08-01',
            'fecha_limite_carga' => '2026-08-31',
            'fecha_inicio_evaluacion' => '2026-09-01',
            'fecha_cierre_prevista' => '2026-09-30',
            'responsables' => $model->dominios->mapWithKeys(fn ($domain, $index) => [$domain->id => $index === 0 ? $responsible->id : $otherResponsible->id])->all(),
            'evaluadores' => [$evaluator->id],
        ], $administrator);
        $this->actingAs($administrator)->post(route('admin.evaluations.start', $evaluation));

        $domains = $evaluation->dominios()->with('dominio')->orderBy('id')->get();
        $ownDomain = $domains->first();
        $otherDomain = $domains->get(1);
        $ownDescriptor = $this->descriptorForDomain($evaluation, $ownDomain);
        $otherDescriptor = $this->descriptorForDomain($evaluation, $otherDomain);

        return [$evaluation->fresh(), $ownDomain, $otherDomain, $responsible, $evaluator, $ownDescriptor, $otherDescriptor];
    }

    private function descriptorForDomain(Evaluacion $evaluation, EvaluacionDominio $domain): EvaluacionDescriptor
    {
        return $evaluation->descriptores()->whereHas('descriptor.criterio', fn ($query) => $query->where('dominio_id', $domain->dominio_id))->firstOrFail();
    }

    private function userWithRole(CodigoRol $roleCode): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('codigo', $roleCode->value)->firstOrFail();
        $user->roles()->attach($role, ['created_at' => now()]);

        return $user;
    }

    private function fileStoreRoute(Evaluacion $evaluation, EvaluacionDescriptor $descriptor): string
    {
        return route('evaluations.descriptors.files.store', [$evaluation, $descriptor]);
    }

    private function pdf(string $name, string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n{$content}");
    }
}
