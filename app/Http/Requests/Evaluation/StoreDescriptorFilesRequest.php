<?php

namespace App\Http\Requests\Evaluation;

use App\Models\Evaluacion;
use App\Models\EvaluacionDescriptor;
use Illuminate\Foundation\Http\FormRequest;

class StoreDescriptorFilesRequest extends FormRequest
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
            'archivos' => ['required', 'array', 'min:1', 'max:10'],
            'archivos.*' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,ppt,pptx,txt,rtf',
            ],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivos.required' => 'Selecciona al menos un archivo.',
            'archivos.max' => 'Puedes cargar un máximo de 10 archivos a la vez.',
            'archivos.*.max' => 'Cada archivo puede pesar hasta 10 MB.',
            'archivos.*.mimes' => 'Solo se permiten PDF, imágenes, documentos de oficina, TXT y RTF.',
        ];
    }
}
