<?php

namespace App\Actions;

use App\Enums\CalificacionDescriptor;
use App\Enums\EstadoEvaluacion;
use App\Enums\EstadoEvaluacionDescriptor;
use App\Enums\EstadoObservacion;
use App\Models\Evaluacion;
use App\Models\EvaluacionDescriptor;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewDescriptor
{
    public function __construct(private readonly AuditService $audit) {}

    public function execute(Evaluacion $evaluation, EvaluacionDescriptor $descriptor, User $evaluator, CalificacionDescriptor $rating, ?string $comment): void
    {
        DB::transaction(function () use ($evaluation, $descriptor, $evaluator, $rating, $comment): void {
            $locked = EvaluacionDescriptor::query()->lockForUpdate()->findOrFail($descriptor->id);
            abort_unless($locked->evaluacion_id === $evaluation->id, 404);
            if ($evaluation->estado !== EstadoEvaluacion::EnEvaluacion) {
                throw ValidationException::withMessages(['calificacion' => 'La evaluación no se encuentra en fase de revisión.']);
            }
            if (! $locked->archivos()->exists()) {
                throw ValidationException::withMessages(['calificacion' => 'No se puede calificar un descriptor sin archivos de evidencia.']);
            }
            if ($locked->observaciones()->where('estado', '!=', EstadoObservacion::Cerrada->value)->exists()) {
                throw ValidationException::withMessages(['calificacion' => 'Cierra las observaciones pendientes antes de calificar.']);
            }

            $before = $locked->getAttributes();
            $locked->historialCalificaciones()->create([
                'calificacion_anterior' => $locked->calificacion?->value,
                'calificacion_nueva' => $rating->value,
                'observacion_anterior' => $locked->observacion_evaluador,
                'observacion_nueva' => $comment,
                'calificada_por' => $evaluator->id,
                'calificada_at' => now(),
            ]);
            $locked->update([
                'estado' => EstadoEvaluacionDescriptor::Evaluado,
                'calificacion' => $rating,
                'calificacion_automatica' => false,
                'motivo_calificacion' => null,
                'observacion_evaluador' => $comment,
                'evaluado_por' => $evaluator->id,
                'evaluado_at' => now(),
            ]);
            $this->audit->recordModel('DESCRIPTOR_CALIFICADO', $locked, $before);
        });
    }
}
