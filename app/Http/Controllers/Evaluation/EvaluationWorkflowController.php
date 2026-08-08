<?php

namespace App\Http\Controllers\Evaluation;

use App\Http\Controllers\Controller;
use App\Models\Evaluacion;
use App\Services\AuditService;
use App\Services\EvaluationWorkflowService;
use Illuminate\Http\RedirectResponse;

class EvaluationWorkflowController extends Controller
{
    public function start(Evaluacion $evaluacion, EvaluationWorkflowService $workflow, AuditService $audit): RedirectResponse
    {
        $this->authorize('start', $evaluacion);
        $workflow->enableEvidenceLoading($evaluacion);
        $audit->recordModel('EVALUACION_HABILITADA', $evaluacion->fresh());

        return back()->with('status', 'La carga de evidencias fue habilitada.');
    }

    public function cancel(Evaluacion $evaluacion, EvaluationWorkflowService $workflow, AuditService $audit): RedirectResponse
    {
        $this->authorize('cancel', $evaluacion);
        $workflow->cancel($evaluacion);
        $audit->recordModel('EVALUACION_CANCELADA', $evaluacion->fresh());

        return back()->with('status', 'Evaluación cancelada.');
    }

    public function startReview(Evaluacion $evaluacion, EvaluationWorkflowService $workflow, AuditService $audit): RedirectResponse
    {
        $this->authorize('startReview', $evaluacion);
        $workflow->startReview($evaluacion);
        $audit->recordModel('EVALUACION_REVISION_INICIADA', $evaluacion->fresh());

        return back()->with('status', 'La revisión de evidencias fue iniciada.');
    }
}
