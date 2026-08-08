<?php

namespace App\Http\Controllers\Evaluation;

use App\Actions\ManageDescriptorObservation;
use App\Actions\ReviewDescriptor;
use App\Enums\CalificacionDescriptor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluation\RespondObservationRequest;
use App\Http\Requests\Evaluation\ReviewDescriptorRequest;
use App\Http\Requests\Evaluation\StoreObservationRequest;
use App\Models\Evaluacion;
use App\Models\EvaluacionDescriptor;
use App\Models\Observacion;
use Illuminate\Http\RedirectResponse;

class DescriptorReviewController extends Controller
{
    public function grade(ReviewDescriptorRequest $request, Evaluacion $evaluacion, EvaluacionDescriptor $evaluacionDescriptor, ReviewDescriptor $action): RedirectResponse
    {
        $action->execute($evaluacion, $evaluacionDescriptor, $request->user(), CalificacionDescriptor::from($request->integer('calificacion')), $request->validated('observacion_evaluador'));

        return back()->with('status', 'Calificación guardada correctamente.');
    }

    public function observe(StoreObservationRequest $request, Evaluacion $evaluacion, EvaluacionDescriptor $evaluacionDescriptor, ManageDescriptorObservation $action): RedirectResponse
    {
        $action->open($evaluacion, $evaluacionDescriptor, $request->user(), $request->validated());

        return back()->with('status', 'Observación enviada al responsable del dominio.');
    }

    public function respond(RespondObservationRequest $request, Evaluacion $evaluacion, EvaluacionDescriptor $evaluacionDescriptor, Observacion $observacion, ManageDescriptorObservation $action): RedirectResponse
    {
        $action->respond($evaluacion, $evaluacionDescriptor, $observacion, $request->user(), $request->validated('respuesta'));

        return back()->with('status', 'Respuesta enviada al evaluador.');
    }

    public function close(Evaluacion $evaluacion, EvaluacionDescriptor $evaluacionDescriptor, Observacion $observacion, ManageDescriptorObservation $action): RedirectResponse
    {
        $this->authorize('review', $evaluacion);
        abort_unless($evaluacionDescriptor->evaluacion_id === $evaluacion->id, 404);
        abort_unless($observacion->evaluacion_descriptor_id === $evaluacionDescriptor->id, 404);
        $action->close($evaluacion, $evaluacionDescriptor, $observacion, request()->user());

        return back()->with('status', 'Observación cerrada. El descriptor puede calificarse.');
    }
}
