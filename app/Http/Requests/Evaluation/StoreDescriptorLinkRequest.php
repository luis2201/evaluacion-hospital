<?php

namespace App\Http\Requests\Evaluation;

use App\Models\Evaluacion;
use App\Models\EvaluacionDescriptor;
use Illuminate\Foundation\Http\FormRequest;

class StoreDescriptorLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $evaluation = $this->route('evaluacion');
        $evaluationDescriptor = $this->route('evaluacionDescriptor');

        return $evaluation instanceof Evaluacion
            && $evaluationDescriptor instanceof EvaluacionDescriptor
            && $this->user()?->can('manageEvidence', [$evaluation, $evaluationDescriptor]);
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url:http,https', 'max:2048'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ];
    }
}
