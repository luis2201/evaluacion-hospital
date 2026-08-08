<?php

namespace App\Http\Requests\Evaluation;

use App\Models\Evaluacion;
use App\Models\EvaluacionDescriptor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewDescriptorRequest extends FormRequest
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
            'calificacion' => ['required', 'integer', Rule::in([0, 1, 2])],
            'observacion_evaluador' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
