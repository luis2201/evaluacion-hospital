<?php

namespace App\Services;

use App\Enums\EstadoAutoevaluacion;
use App\Enums\EstadoEvaluacion;
use App\Enums\EstadoObservacion;
use App\Models\Evaluacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EvaluationResultService
{
    /** @return array{general: object|null, domains: Collection<int, object>, criteria: Collection<int, object>, submitted_self_assessments: int, missing_self_assessments: int, pending_self_assessments: int, total_domains: int, open_observations: int, noncompliant_descriptors: int, pending_review_descriptors: int, pending_evidence_descriptors: int, timeline: array<string, bool>} */
    public function for(Evaluacion $evaluation): array
    {
        $today = today();
        $loadingExpired = $evaluation->fecha_limite_carga !== null && $today->gt($evaluation->fecha_limite_carga);
        $reviewStarted = $evaluation->fecha_inicio_evaluacion !== null && $today->gte($evaluation->fecha_inicio_evaluacion);

        $submittedSelfAssessments = $evaluation->dominios()->whereHas('autoevaluacion', fn ($query) => $query->where('estado', EstadoAutoevaluacion::Enviada->value))->count();
        $missingSelfAssessments = $evaluation->dominios()->whereHas('autoevaluacion', fn ($query) => $query->where('estado', EstadoAutoevaluacion::Incumplida->value))->count();
        $totalDomains = $evaluation->dominios()->count();

        return [
            'general' => DB::table('vw_resultados_generales')->where('evaluacion_id', $evaluation->id)->first(),
            'domains' => DB::table('vw_resultados_dominios')->where('evaluacion_id', $evaluation->id)->orderBy('dominio_codigo')->get(),
            'criteria' => DB::table('vw_resultados_criterios')->where('evaluacion_id', $evaluation->id)->orderBy('criterio_codigo')->get(),
            'submitted_self_assessments' => $submittedSelfAssessments,
            'missing_self_assessments' => $missingSelfAssessments,
            'pending_self_assessments' => max(0, $totalDomains - $submittedSelfAssessments - $missingSelfAssessments),
            'total_domains' => $totalDomains,
            'open_observations' => $evaluation->descriptores()->whereHas('observaciones', fn ($query) => $query->whereIn('estado', [EstadoObservacion::Abierta->value, EstadoObservacion::Respondida->value]))->count(),
            'noncompliant_descriptors' => $evaluation->descriptores()->where('calificacion', 0)->count(),
            'pending_review_descriptors' => $evaluation->descriptores()->whereNull('calificacion')->count(),
            'pending_evidence_descriptors' => $evaluation->descriptores()->whereNull('calificacion')->whereDoesntHave('archivos')->count(),
            'timeline' => [
                'scheduled' => $evaluation->fecha_inicio !== null && $today->lt($evaluation->fecha_inicio),
                'loading_open' => $evaluation->fecha_inicio !== null && $evaluation->fecha_limite_carga !== null && $today->betweenIncluded($evaluation->fecha_inicio, $evaluation->fecha_limite_carga),
                'loading_expired' => $loadingExpired,
                'review_started' => $reviewStarted,
                'closing_overdue' => $evaluation->estado !== EstadoEvaluacion::Cerrada && $evaluation->fecha_cierre_prevista !== null && $today->gt($evaluation->fecha_cierre_prevista),
            ],
        ];
    }
}
