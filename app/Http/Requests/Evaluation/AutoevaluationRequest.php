<?php

namespace App\Http\Requests\Evaluation;

use App\Actions\SaveDomainSelfAssessment;
use App\Enums\EstadoAutoevaluacion;
use App\Models\Evaluacion;
use App\Models\EvaluacionDominio;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AutoevaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $evaluation = $this->route('evaluacion');
        $evaluationDomain = $this->route('evaluacionDominio');

        return $evaluation instanceof Evaluacion
            && $evaluationDomain instanceof EvaluacionDominio
            && $evaluationDomain->evaluacion_id === $evaluation->id
            && $evaluationDomain->responsable_id === $this->user()?->id
            && $this->user()->can('view', $evaluation);
    }

    public function rules(): array
    {
        return [
            'contenido' => [
                'required',
                'string',
                'max:10000',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (SaveDomainSelfAssessment::wordCount((string) $value) > 250) {
                        $fail('La autoevaluación no puede superar las 250 palabras.');
                    }
                },
            ],
            'estado' => ['required', Rule::in([EstadoAutoevaluacion::Borrador->value, EstadoAutoevaluacion::Enviada->value])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['contenido' => trim($this->input('contenido', ''))]);
    }
}
