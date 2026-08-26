<?php

namespace App\Actions;

use App\Enums\EstadoAutoevaluacion;
use App\Enums\EstadoEvaluacion;
use App\Enums\EstadoEvaluacionDominio;
use App\Enums\EstadoObservacion;
use App\Models\Evaluacion;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseEvaluation
{
    public function __construct(private readonly AuditService $audit) {}

    public function execute(Evaluacion $evaluation, User $user): void
    {
        DB::transaction(function () use ($evaluation, $user): void {
            $locked = Evaluacion::query()->lockForUpdate()->findOrFail($evaluation->id);
            if ($locked->estado !== EstadoEvaluacion::EnEvaluacion) {
                $this->fail('Solo se puede cerrar una evaluación que se encuentre en revisión.');
            }

            $totalDomains = $locked->dominios()->count();
            $finalized = $locked->dominios()->whereHas('autoevaluacion', fn ($query) => $query->whereIn('estado', [EstadoAutoevaluacion::Enviada->value, EstadoAutoevaluacion::Incumplida->value]))->count();
            if ($totalDomains === 0 || $finalized !== $totalDomains) {
                $this->fail("Faltan autoevaluaciones por clasificar ({$finalized} de {$totalDomains}).");
            }

            $totalDescriptors = $locked->descriptores()->count();
            $ratedDescriptors = $locked->descriptores()->whereNotNull('calificacion')->count();
            if ($totalDescriptors === 0 || $ratedDescriptors !== $totalDescriptors) {
                $this->fail("Faltan descriptores por calificar ({$ratedDescriptors} de {$totalDescriptors}).");
            }

            $openObservations = $locked->descriptores()->whereHas('observaciones', fn ($query) => $query->whereIn('estado', [EstadoObservacion::Abierta->value, EstadoObservacion::Respondida->value]))->count();
            if ($openObservations > 0) {
                $this->fail("Existen {$openObservations} descriptores con observaciones pendientes.");
            }

            $result = DB::table('vw_resultados_generales')->where('evaluacion_id', $locked->id)->first();
            if (! $result || $result->estado_calculo !== 'COMPLETA' || $result->categoria_final === null) {
                $this->fail('El resultado general no está completo o no corresponde a una categoría configurada.');
            }

            $before = $locked->getAttributes();
            $closedAt = now();
            $locked->update([
                'estado' => EstadoEvaluacion::Cerrada,
                'fecha_cierre' => $closedAt->toDateString(),
                'cerrada_por' => $user->id,
                'cerrada_at' => $closedAt,
            ]);
            $locked->dominios()->update([
                'estado' => EstadoEvaluacionDominio::Cerrado,
                'completado_at' => $closedAt,
            ]);

            $this->audit->recordModel('EVALUACION_CERRADA', $locked, $before);
        });
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['cierre' => $message]);
    }
}
