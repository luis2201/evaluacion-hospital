<?php

namespace App\Actions;

use App\Enums\EstadoEvaluacion;
use App\Enums\EstadoEvaluacionDescriptor;
use App\Models\DescriptorArchivo;
use App\Models\Evaluacion;
use App\Models\User;
use App\Services\AuditService;
use App\Services\EvaluationCalendarService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteDescriptorFile
{
    public function __construct(private readonly AuditService $audit, private readonly EvaluationCalendarService $calendar) {}

    public function execute(Evaluacion $evaluation, DescriptorArchivo $file, User $user): void
    {
        $descriptor = $file->evaluacionDescriptor;
        $isRemediation = $evaluation->estado === EstadoEvaluacion::EnEvaluacion
            && $descriptor->estado === EstadoEvaluacionDescriptor::Observado
            && $descriptor->observaciones()->where('estado', 'ABIERTA')->exists();
        if (! $this->calendar->isLoadingOpen($evaluation) && ! $isRemediation) {
            throw ValidationException::withMessages(['archivo' => 'Los archivos solo pueden eliminarse durante la fase de carga de evidencias.']);
        }

        DB::transaction(function () use ($file, $user): void {
            $lockedFile = DescriptorArchivo::query()->lockForUpdate()->findOrFail($file->id);
            $before = $lockedFile->getAttributes();
            $lockedFile->update(['eliminado_por' => $user->id]);
            $lockedFile->delete();
            $this->audit->recordModel('EVIDENCIA_ARCHIVO_ELIMINADO', $lockedFile, $before);
        });
    }
}
