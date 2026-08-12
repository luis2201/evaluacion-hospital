<?php

namespace App\Http\Requests\Evaluation;

use App\Enums\EstadoEvaluacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEvaluationScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageSchedule', $this->route('evaluacion')) ?? false;
    }

    public function rules(): array
    {
        return [
            'fecha_inicio' => ['required', 'date'],
            'fecha_limite_carga' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'fecha_inicio_evaluacion' => ['required', 'date', 'after:fecha_limite_carga'],
            'fecha_cierre_prevista' => ['required', 'date', 'after_or_equal:fecha_inicio_evaluacion'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $evaluation = $this->route('evaluacion');
            if ($evaluation->estado === EstadoEvaluacion::CargaEvidencias
                && $this->date('fecha_inicio')?->isAfter(today())) {
                $validator->errors()->add('fecha_inicio', 'No se puede trasladar al futuro el inicio de una carga que ya está activa.');
            }
            if ($evaluation->estado === EstadoEvaluacion::EnEvaluacion
                && $this->date('fecha_inicio_evaluacion')?->isAfter(today())) {
                $validator->errors()->add('fecha_inicio_evaluacion', 'No se puede reabrir la carga documental de una evaluación que ya está en revisión.');
            }
        }];
    }
}
