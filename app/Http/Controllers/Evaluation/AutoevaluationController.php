<?php

namespace App\Http\Controllers\Evaluation;

use App\Actions\SaveDomainSelfAssessment;
use App\Enums\EstadoAutoevaluacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluation\AutoevaluationRequest;
use App\Models\Evaluacion;
use App\Models\EvaluacionDominio;
use Illuminate\Http\RedirectResponse;

class AutoevaluationController extends Controller
{
    public function store(AutoevaluationRequest $request, Evaluacion $evaluacion, EvaluacionDominio $evaluacionDominio, SaveDomainSelfAssessment $action): RedirectResponse
    {
        $data = $request->validated();
        $state = EstadoAutoevaluacion::from($data['estado']);
        $action->execute($evaluacion, $evaluacionDominio, $request->user(), $data['contenido'], $state);

        return back()->with('status', $state === EstadoAutoevaluacion::Enviada ? 'Autoevaluación enviada definitivamente.' : 'Autoevaluación guardada como borrador.');
    }
}
