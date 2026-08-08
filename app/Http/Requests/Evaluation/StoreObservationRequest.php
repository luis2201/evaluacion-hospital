<?php

namespace App\Http\Requests\Evaluation;

use App\Models\Evaluacion;
use App\Models\EvaluacionDescriptor;
use Illuminate\Foundation\Http\FormRequest;

class StoreObservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $evaluation = $this->route('evaluacion');
        $descriptor = $this->route('evaluacionDescriptor');

        return $evaluation instanceof Evaluacion
            && $descriptor instanceof EvaluacionDescriptor
            && $descriptor->evaluacion_id === $evaluation->id
            && $this->user()?->can('review', $evaluation);
    }

    public function rules(): array
    {
        return [
            'asunto' => ['required', 'string', 'max:255'],
            'detalle' => ['required', 'string', 'max:5000'],
            'fecha_limite' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
