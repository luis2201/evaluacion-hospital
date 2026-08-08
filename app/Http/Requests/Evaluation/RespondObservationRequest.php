<?php

namespace App\Http\Requests\Evaluation;

use App\Models\Evaluacion;
use App\Models\EvaluacionDescriptor;
use App\Models\Observacion;
use Illuminate\Foundation\Http\FormRequest;

class RespondObservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $evaluation = $this->route('evaluacion');
        $descriptor = $this->route('evaluacionDescriptor');
        $observation = $this->route('observacion');

        return $evaluation instanceof Evaluacion
            && $descriptor instanceof EvaluacionDescriptor
            && $observation instanceof Observacion
            && $observation->evaluacion_descriptor_id === $descriptor->id
            && $this->user()?->can('respondObservation', [$evaluation, $descriptor]);
    }

    public function rules(): array
    {
        return ['respuesta' => ['required', 'string', 'max:5000']];
    }
}
