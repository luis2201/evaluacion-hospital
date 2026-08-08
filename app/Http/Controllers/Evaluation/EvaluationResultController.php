<?php

namespace App\Http\Controllers\Evaluation;

use App\Actions\CloseEvaluation;
use App\Http\Controllers\Controller;
use App\Models\Evaluacion;
use App\Services\EvaluationCalendarService;
use App\Services\EvaluationResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationResultController extends Controller
{
    public function show(Request $request, Evaluacion $evaluacion, EvaluationCalendarService $calendar, EvaluationResultService $results): View
    {
        $this->authorize('viewResults', $evaluacion);
        $calendar->sync($evaluacion);
        $evaluacion->load(['modeloEvaluacion', 'cerrador']);

        return view('evaluations.results', ['evaluacion' => $evaluacion] + $results->for($evaluacion));
    }

    public function close(Request $request, Evaluacion $evaluacion, CloseEvaluation $action): RedirectResponse
    {
        $this->authorize('close', $evaluacion);
        $action->execute($evaluacion, $request->user());

        return redirect()->route('evaluations.results', $evaluacion)->with('status', 'La evaluación fue cerrada formalmente. El resultado oficial quedó protegido.');
    }
}
