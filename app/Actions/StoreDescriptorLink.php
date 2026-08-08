<?php

namespace App\Actions;

use App\Enums\EstadoEvaluacion;
use App\Enums\EstadoEvaluacionDescriptor;
use App\Models\DescriptorEnlace;
use App\Models\Evaluacion;
use App\Models\EvaluacionDescriptor;
use App\Models\User;
use App\Services\AuditService;
use App\Services\EvaluationCalendarService;
use Illuminate\Validation\ValidationException;

class StoreDescriptorLink
{
    public function __construct(private readonly AuditService $audit, private readonly EvaluationCalendarService $calendar) {}

    public function execute(Evaluacion $evaluation, EvaluacionDescriptor $evaluationDescriptor, User $user, string $url, ?string $description): DescriptorEnlace
    {
        $isRemediation = $evaluation->estado === EstadoEvaluacion::EnEvaluacion
            && $evaluationDescriptor->estado === EstadoEvaluacionDescriptor::Observado
            && $evaluationDescriptor->observaciones()->where('estado', 'ABIERTA')->exists();
        if (! $this->calendar->isLoadingOpen($evaluation) && ! $isRemediation) {
            throw ValidationException::withMessages(['url' => 'La evaluación no se encuentra en la fase de carga de evidencias.']);
        }

        abort_unless($evaluationDescriptor->evaluacion_id === $evaluation->id, 404);

        if ($evaluationDescriptor->enlaces()->where('url', $url)->exists()) {
            throw ValidationException::withMessages(['url' => 'Este enlace ya está registrado en el descriptor.']);
        }

        $link = $evaluationDescriptor->enlaces()->create([
            'url' => $url,
            'descripcion' => $description,
            'registrado_por' => $user->id,
        ]);
        $this->audit->recordModel('EVIDENCIA_ENLACE_REGISTRADO', $link);

        return $link;
    }
}
