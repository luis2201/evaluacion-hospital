<?php

namespace App\Services;

use App\Enums\EstadoAutoevaluacion;
use App\Enums\EstadoObservacion;
use App\Models\Evaluacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EvaluationResultService
{
    /** @return array{general: object|null, domains: Collection<int, object>, criteria: Collection<int, object>, submitted_self_assessments: int, total_domains: int, open_observations: int} */
    public function for(Evaluacion $evaluation): array
    {
        return [
            'general' => DB::table('vw_resultados_generales')->where('evaluacion_id', $evaluation->id)->first(),
            'domains' => DB::table('vw_resultados_dominios')->where('evaluacion_id', $evaluation->id)->orderBy('dominio_codigo')->get(),
            'criteria' => DB::table('vw_resultados_criterios')->where('evaluacion_id', $evaluation->id)->orderBy('criterio_codigo')->get(),
            'submitted_self_assessments' => $evaluation->dominios()->whereHas('autoevaluacion', fn ($query) => $query->where('estado', EstadoAutoevaluacion::Enviada->value))->count(),
            'total_domains' => $evaluation->dominios()->count(),
            'open_observations' => $evaluation->descriptores()->whereHas('observaciones', fn ($query) => $query->whereIn('estado', [EstadoObservacion::Abierta->value, EstadoObservacion::Respondida->value]))->count(),
        ];
    }
}
