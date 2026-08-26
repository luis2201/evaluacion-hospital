<?php

namespace App\Http\Requests\Evaluation;

use App\Models\Evaluacion;
use App\Models\EvaluacionDescriptor;
use App\Services\SystemSettingsService;
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
        $settings = app(SystemSettingsService::class);

        return [
            'archivos' => ['required', 'array', 'min:1', 'max:'.$settings->integer('max_upload_files')],
            'archivos.*' => [
                'required',
                'file',
                'max:'.($settings->integer('max_file_size_mb') * 1024),
                'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,ppt,pptx,txt,rtf',
            ],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        $settings = app(SystemSettingsService::class);

        return [
            'archivos.required' => 'Selecciona al menos un archivo.',
            'archivos.max' => 'Puedes cargar un máximo de '.$settings->integer('max_upload_files').' archivos a la vez.',
            'archivos.*.max' => 'Cada archivo puede pesar hasta '.$settings->integer('max_file_size_mb').' MB.',
            'archivos.*.mimes' => 'Solo se permiten PDF, imágenes, documentos de oficina, TXT y RTF.',
        ];
    }
}
