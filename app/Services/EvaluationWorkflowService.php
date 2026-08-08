<?php

namespace App\Services;

use App\Enums\EstadoEvaluacion;
use App\Models\Evaluacion;
use Illuminate\Validation\ValidationException;

class EvaluationWorkflowService
{
    public function __construct(private readonly EvaluationCalendarService $calendar) {}

    public function enableEvidenceLoading(Evaluacion $evaluation): void
    {
        if ($evaluation->estado !== EstadoEvaluacion::Borrador) {
            $this->invalidTransition();
        }
        if ($evaluation->dominios()->count() === 0 || $evaluation->descriptores()->count() === 0 || $evaluation->evaluadores()->count() === 0) {
            throw ValidationException::withMessages(['estado' => 'La evaluación no tiene todas las asignaciones o ítems requeridos.']);
        }

        $this->calendar->sync($evaluation);
        if ($evaluation->estado !== EstadoEvaluacion::CargaEvidencias) {
            throw ValidationException::withMessages(['estado' => 'La carga se habilita automáticamente en la fecha de inicio configurada.']);
        }
    }

    public function cancel(Evaluacion $evaluation): void
    {
        if (in_array($evaluation->estado, [EstadoEvaluacion::Cerrada, EstadoEvaluacion::Cancelada], true)) {
            $this->invalidTransition();
        }
        $evaluation->update(['estado' => EstadoEvaluacion::Cancelada]);
    }

    public function startReview(Evaluacion $evaluation): void
    {
        if ($evaluation->estado !== EstadoEvaluacion::CargaEvidencias) {
            $this->invalidTransition();
        }

        $this->calendar->sync($evaluation);
        if ($evaluation->estado !== EstadoEvaluacion::EnEvaluacion) {
            throw ValidationException::withMessages(['estado' => 'La revisión inicia automáticamente en la fecha configurada.']);
        }
    }

    private function invalidTransition(): never
    {
        throw ValidationException::withMessages(['estado' => 'La transición solicitada no es válida para el estado actual.']);
    }
}
