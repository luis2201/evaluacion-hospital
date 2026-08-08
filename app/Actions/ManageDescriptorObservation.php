<?php

namespace App\Actions;

use App\Enums\EstadoEvaluacion;
use App\Enums\EstadoEvaluacionDescriptor;
use App\Enums\EstadoObservacion;
use App\Models\Evaluacion;
use App\Models\EvaluacionDescriptor;
use App\Models\Observacion;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageDescriptorObservation
{
    public function __construct(private readonly AuditService $audit) {}

    public function open(Evaluacion $evaluation, EvaluacionDescriptor $descriptor, User $evaluator, array $data): Observacion
    {
        return DB::transaction(function () use ($evaluation, $descriptor, $evaluator, $data): Observacion {
            $locked = EvaluacionDescriptor::query()->lockForUpdate()->findOrFail($descriptor->id);
            $this->ensureReviewState($evaluation, $locked);
            if ($locked->calificacion !== null) {
                throw ValidationException::withMessages(['observacion' => 'Un descriptor ya calificado no puede observarse.']);
            }
            if ($locked->observaciones()->where('estado', '!=', EstadoObservacion::Cerrada->value)->exists()) {
                throw ValidationException::withMessages(['observacion' => 'El descriptor ya tiene una observación pendiente.']);
            }
            $observation = $locked->observaciones()->create($data + ['creada_por' => $evaluator->id, 'estado' => EstadoObservacion::Abierta]);
            $locked->update(['estado' => EstadoEvaluacionDescriptor::Observado]);
            $this->audit->recordModel('OBSERVACION_CREADA', $observation);

            return $observation;
        });
    }

    public function respond(Evaluacion $evaluation, EvaluacionDescriptor $descriptor, Observacion $observation, User $responsible, string $answer): void
    {
        DB::transaction(function () use ($evaluation, $descriptor, $observation, $responsible, $answer): void {
            $this->ensureReviewState($evaluation, $descriptor);
            $locked = Observacion::query()->lockForUpdate()->findOrFail($observation->id);
            abort_unless($locked->evaluacion_descriptor_id === $descriptor->id, 404);
            if ($locked->estado !== EstadoObservacion::Abierta) {
                throw ValidationException::withMessages(['respuesta' => 'La observación ya fue respondida o cerrada.']);
            }
            $response = $locked->respuestas()->create(['respondida_por' => $responsible->id, 'respuesta' => $answer]);
            $locked->update(['estado' => EstadoObservacion::Respondida]);
            $this->audit->recordModel('OBSERVACION_RESPONDIDA', $response);
        });
    }

    public function close(Evaluacion $evaluation, EvaluacionDescriptor $descriptor, Observacion $observation, User $evaluator): void
    {
        DB::transaction(function () use ($evaluation, $descriptor, $observation, $evaluator): void {
            $this->ensureReviewState($evaluation, $descriptor);
            $locked = Observacion::query()->lockForUpdate()->findOrFail($observation->id);
            abort_unless($locked->evaluacion_descriptor_id === $descriptor->id, 404);
            if ($locked->estado !== EstadoObservacion::Respondida) {
                throw ValidationException::withMessages(['observacion' => 'Solo puede cerrarse una observación respondida.']);
            }
            $locked->update(['estado' => EstadoObservacion::Cerrada, 'cerrada_por' => $evaluator->id, 'cerrada_at' => now()]);
            $descriptor->update(['estado' => EstadoEvaluacionDescriptor::EnEvaluacion]);
            $this->audit->recordModel('OBSERVACION_CERRADA', $locked);
        });
    }

    private function ensureReviewState(Evaluacion $evaluation, EvaluacionDescriptor $descriptor): void
    {
        abort_unless($descriptor->evaluacion_id === $evaluation->id, 404);
        if ($evaluation->estado !== EstadoEvaluacion::EnEvaluacion) {
            throw ValidationException::withMessages(['observacion' => 'La evaluación no se encuentra en fase de revisión.']);
        }
    }
}
