<?php

namespace App\Actions;

use App\Enums\EstadoEvaluacion;
use App\Enums\EstadoEvaluacionDescriptor;
use App\Models\DescriptorEnlace;
use App\Models\Evaluacion;
use App\Models\User;
use App\Services\AuditService;
use App\Services\EvaluationCalendarService;
use Illuminate\Validation\ValidationException;

class DeleteDescriptorLink
{
    public function __construct(private readonly AuditService $audit, private readonly EvaluationCalendarService $calendar) {}

    public function execute(Evaluacion $evaluation, DescriptorEnlace $link, User $user): void
    {
        $descriptor = $link->evaluacionDescriptor;
        $isRemediation = $evaluation->estado === EstadoEvaluacion::EnEvaluacion
            && $descriptor->estado === EstadoEvaluacionDescriptor::Observado
            && $descriptor->observaciones()->where('estado', 'ABIERTA')->exists();
        if (! $this->calendar->isLoadingOpen($evaluation) && ! $isRemediation) {
            throw ValidationException::withMessages(['enlace' => 'Los enlaces solo pueden retirarse durante la fase de carga de evidencias.']);
        }

        $before = $link->getAttributes();
        $link->delete();
        $this->audit->record('EVIDENCIA_ENLACE_ELIMINADO', $link->getTable(), $link->id, $before, $link->getAttributes(), $user->id);
    }
}
