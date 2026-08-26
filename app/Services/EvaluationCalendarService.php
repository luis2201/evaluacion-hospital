<?php

namespace App\Services;

use App\Enums\EstadoAutoevaluacion;
use App\Enums\EstadoEvaluacion;
use App\Enums\EstadoEvaluacionDescriptor;
use App\Enums\EstadoEvaluacionDominio;
use App\Models\Auditoria;
use App\Models\Evaluacion;
use App\Models\User;
use App\Notifications\EvaluationEventNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EvaluationCalendarService
{
    public function syncAll(): int
    {
        $updated = 0;
        Evaluacion::query()
            ->whereIn('estado', [EstadoEvaluacion::Borrador->value, EstadoEvaluacion::CargaEvidencias->value])
            ->pluck('id')
            ->each(function (int $id) use (&$updated): void {
                $updated += $this->syncById($id) ? 1 : 0;
            });

        return $updated;
    }

    public function sync(Evaluacion $evaluation): bool
    {
        $changed = $this->syncById($evaluation->id);
        if ($changed) {
            $evaluation->refresh();
        }

        return $changed;
    }

    public function isLoadingOpen(Evaluacion $evaluation): bool
    {
        $today = today();
        $reviewDate = $this->reviewDate($evaluation);

        return $evaluation->estado === EstadoEvaluacion::CargaEvidencias
            && $evaluation->fecha_inicio !== null
            && $evaluation->fecha_limite_carga !== null
            && $today->betweenIncluded($evaluation->fecha_inicio, $evaluation->fecha_limite_carga)
            && ($reviewDate === null || $today->lt($reviewDate));
    }

    public function reviewDate(Evaluacion $evaluation): ?Carbon
    {
        return $evaluation->fecha_inicio_evaluacion
            ?? $evaluation->fecha_limite_carga?->copy()->addDay();
    }

    /** @return array{label: string, variant: string} */
    public function displayState(Evaluacion $evaluation): array
    {
        if ($evaluation->estado === EstadoEvaluacion::Cerrada) {
            return ['label' => 'Cerrada', 'variant' => 'success'];
        }
        if ($evaluation->estado === EstadoEvaluacion::Cancelada) {
            return ['label' => 'Cancelada', 'variant' => 'warning'];
        }

        $today = today();

        return match (true) {
            $evaluation->fecha_inicio !== null && $today->lt($evaluation->fecha_inicio) => ['label' => 'Programada', 'variant' => 'neutral'],
            $evaluation->fecha_inicio !== null && $evaluation->fecha_limite_carga !== null && $today->betweenIncluded($evaluation->fecha_inicio, $evaluation->fecha_limite_carga) => ['label' => 'Carga de evidencias', 'variant' => 'info'],
            $evaluation->fecha_cierre_prevista !== null && $today->gt($evaluation->fecha_cierre_prevista) => ['label' => 'Cierre previsto vencido', 'variant' => 'warning'],
            $this->reviewDate($evaluation) !== null && $today->gte($this->reviewDate($evaluation)) => ['label' => 'En evaluación', 'variant' => 'info'],
            default => ['label' => 'Carga finalizada', 'variant' => 'warning'],
        };
    }

    private function syncById(int $evaluationId): bool
    {
        return DB::transaction(function () use ($evaluationId): bool {
            $evaluation = Evaluacion::query()->lockForUpdate()->findOrFail($evaluationId);
            if (! in_array($evaluation->estado, [EstadoEvaluacion::Borrador, EstadoEvaluacion::CargaEvidencias], true)) {
                return false;
            }
            if ($evaluation->dominios()->count() === 0 || $evaluation->descriptores()->count() === 0 || $evaluation->evaluadores()->count() === 0) {
                return false;
            }

            $today = today();
            $reviewDate = $this->reviewDate($evaluation);
            $newState = match (true) {
                $reviewDate !== null && $today->gte($reviewDate) => EstadoEvaluacion::EnEvaluacion,
                $evaluation->fecha_inicio !== null && $today->gte($evaluation->fecha_inicio) => EstadoEvaluacion::CargaEvidencias,
                default => null,
            };

            if ($newState === null || $newState === $evaluation->estado) {
                return false;
            }

            $before = $evaluation->getAttributes();
            $evaluation->update(['estado' => $newState]);
            if ($newState === EstadoEvaluacion::CargaEvidencias) {
                $evaluation->dominios()->where('estado', EstadoEvaluacionDominio::Pendiente->value)->update(['estado' => EstadoEvaluacionDominio::EnCarga]);
            }
            if ($newState === EstadoEvaluacion::EnEvaluacion) {
                $evaluation->dominios()->where('estado', EstadoEvaluacionDominio::Pendiente->value)->update(['estado' => EstadoEvaluacionDominio::EnCarga]);
                $missingSelfAssessments = 0;
                foreach ($evaluation->dominios()->with('autoevaluacion')->get() as $domain) {
                    if ($domain->autoevaluacion?->estado === EstadoAutoevaluacion::Enviada) {
                        continue;
                    }
                    $domain->autoevaluacion()->updateOrCreate([], [
                        'contenido' => $domain->autoevaluacion?->contenido ?: 'Autoevaluación no enviada dentro del plazo de carga establecido.',
                        'cantidad_palabras' => $domain->autoevaluacion?->cantidad_palabras ?: 9,
                        'estado' => EstadoAutoevaluacion::Incumplida,
                        'registrada_por' => $domain->responsable_id,
                        'enviada_at' => null,
                    ]);
                    $domain->update(['estado' => EstadoEvaluacionDominio::Incumplido]);
                    $missingSelfAssessments++;
                }
                $missingEvidence = $evaluation->descriptores()
                    ->whereNull('calificacion')
                    ->whereDoesntHave('archivos')
                    ->update([
                        'estado' => EstadoEvaluacionDescriptor::Evaluado->value,
                        'calificacion' => 0,
                        'calificacion_automatica' => true,
                        'motivo_calificacion' => 'ARCHIVO_NO_CARGADO',
                        'observacion_evaluador' => 'Calificación automática por cierre de la fase de carga sin archivo de evidencia.',
                        'evaluado_por' => null,
                        'evaluado_at' => now(),
                    ]);
                $evaluation->descriptores()->where('estado', EstadoEvaluacionDescriptor::Pendiente->value)->update(['estado' => EstadoEvaluacionDescriptor::EnEvaluacion]);
                if ($missingEvidence > 0) {
                    Auditoria::query()->create([
                        'user_id' => null,
                        'accion' => 'DESCRIPTORES_SIN_ARCHIVO_CALIFICADOS',
                        'tabla' => 'evaluacion_descriptores',
                        'registro_id' => $evaluation->id,
                        'valores_anteriores' => null,
                        'valores_nuevos' => ['cantidad' => $missingEvidence, 'calificacion' => 0, 'motivo' => 'ARCHIVO_NO_CARGADO'],
                        'ip_address' => null,
                        'user_agent' => 'Laravel Scheduler',
                    ]);
                }
                if ($missingSelfAssessments > 0) {
                    Auditoria::query()->create([
                        'user_id' => null,
                        'accion' => 'AUTOEVALUACIONES_NO_ENVIADAS',
                        'tabla' => 'autoevaluaciones_dominios',
                        'registro_id' => $evaluation->id,
                        'valores_anteriores' => null,
                        'valores_nuevos' => ['cantidad' => $missingSelfAssessments, 'estado' => EstadoAutoevaluacion::Incumplida->value],
                        'ip_address' => null,
                        'user_agent' => 'Laravel Scheduler',
                    ]);
                }
            }

            Auditoria::query()->create([
                'user_id' => null,
                'accion' => 'EVALUACION_ESTADO_AUTOMATICO',
                'tabla' => $evaluation->getTable(),
                'registro_id' => $evaluation->id,
                'valores_anteriores' => $before,
                'valores_nuevos' => $evaluation->getAttributes(),
                'ip_address' => null,
                'user_agent' => 'Laravel Scheduler',
            ]);

            $recipientIds = $evaluation->evaluadores()->pluck('users.id')->merge($evaluation->dominios()->pluck('responsable_id'))->unique();
            foreach (User::query()->whereIn('id', $recipientIds)->get() as $recipient) {
                $recipient->notify(new EvaluationEventNotification([
                    'title' => $newState === EstadoEvaluacion::CargaEvidencias ? 'Carga de evidencias habilitada' : 'Revisión de evidencias iniciada',
                    'message' => $newState === EstadoEvaluacion::CargaEvidencias
                        ? "La evaluación {$evaluation->codigo} ya permite registrar autoevaluaciones y evidencias."
                        : "La evaluación {$evaluation->codigo} inició su fase de revisión según el cronograma.",
                    'url' => route('evaluations.show', $evaluation),
                ]));
            }

            return true;
        });
    }
}
