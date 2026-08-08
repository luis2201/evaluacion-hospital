<?php

namespace App\Http\Controllers\Evaluation;

use App\Actions\DeleteDescriptorFile;
use App\Actions\DeleteDescriptorLink;
use App\Actions\StoreDescriptorFiles;
use App\Actions\StoreDescriptorLink;
use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluation\StoreDescriptorFilesRequest;
use App\Http\Requests\Evaluation\StoreDescriptorLinkRequest;
use App\Models\DescriptorArchivo;
use App\Models\DescriptorEnlace;
use App\Models\Evaluacion;
use App\Models\EvaluacionDescriptor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DescriptorEvidenceController extends Controller
{
    public function store(StoreDescriptorFilesRequest $request, Evaluacion $evaluacion, EvaluacionDescriptor $evaluacionDescriptor, StoreDescriptorFiles $action): RedirectResponse
    {
        $saved = $action->execute(
            $evaluacion,
            $evaluacionDescriptor,
            $request->user(),
            $request->file('archivos'),
            $request->validated('descripcion'),
        );

        return back()->with('status', $saved === 1 ? 'Archivo cargado correctamente.' : "{$saved} archivos cargados correctamente.");
    }

    public function preview(Evaluacion $evaluacion, EvaluacionDescriptor $evaluacionDescriptor, DescriptorArchivo $descriptorArchivo): StreamedResponse
    {
        $this->authorize('view', $evaluacion);
        $this->ensureRelationships($evaluacion, $evaluacionDescriptor, $descriptorArchivo);
        abort_unless(in_array($descriptorArchivo->mime_type, ['application/pdf', 'image/jpeg', 'image/png'], true), 404);

        return $this->fileResponse($descriptorArchivo, 'inline');
    }

    public function download(Evaluacion $evaluacion, EvaluacionDescriptor $evaluacionDescriptor, DescriptorArchivo $descriptorArchivo): StreamedResponse
    {
        $this->authorize('view', $evaluacion);
        $this->ensureRelationships($evaluacion, $evaluacionDescriptor, $descriptorArchivo);

        $descriptorArchivo->descargas()->create([
            'user_id' => request()->user()->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'descargado_at' => now(),
        ]);

        return $this->fileResponse($descriptorArchivo, 'attachment');
    }

    public function destroy(Evaluacion $evaluacion, EvaluacionDescriptor $evaluacionDescriptor, DescriptorArchivo $descriptorArchivo, DeleteDescriptorFile $action): RedirectResponse
    {
        $this->authorize('manageEvidence', [$evaluacion, $evaluacionDescriptor]);
        $this->ensureRelationships($evaluacion, $evaluacionDescriptor, $descriptorArchivo);
        $action->execute($evaluacion, $descriptorArchivo, request()->user());

        return back()->with('status', 'Archivo retirado correctamente. Puede restaurarse cargándolo nuevamente.');
    }

    public function storeLink(StoreDescriptorLinkRequest $request, Evaluacion $evaluacion, EvaluacionDescriptor $evaluacionDescriptor, StoreDescriptorLink $action): RedirectResponse
    {
        $action->execute($evaluacion, $evaluacionDescriptor, $request->user(), $request->validated('url'), $request->validated('descripcion'));

        return back()->with('status', 'Enlace de evidencia registrado correctamente.');
    }

    public function destroyLink(Evaluacion $evaluacion, EvaluacionDescriptor $evaluacionDescriptor, DescriptorEnlace $descriptorEnlace, DeleteDescriptorLink $action): RedirectResponse
    {
        $this->authorize('manageEvidence', [$evaluacion, $evaluacionDescriptor]);
        abort_unless($evaluacionDescriptor->evaluacion_id === $evaluacion->id, 404);
        abort_unless($descriptorEnlace->evaluacion_descriptor_id === $evaluacionDescriptor->id, 404);
        $action->execute($evaluacion, $descriptorEnlace, request()->user());

        return back()->with('status', 'Enlace retirado correctamente.');
    }

    private function ensureRelationships(Evaluacion $evaluation, EvaluacionDescriptor $evaluationDescriptor, DescriptorArchivo $file): void
    {
        abort_unless($evaluationDescriptor->evaluacion_id === $evaluation->id, 404);
        abort_unless($file->evaluacion_descriptor_id === $evaluationDescriptor->id, 404);
    }

    private function fileResponse(DescriptorArchivo $file, string $disposition): StreamedResponse
    {
        $disk = Storage::disk($file->disco);
        abort_unless($disk->exists($file->ruta), 404);

        $headers = [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => $disposition."; filename*=UTF-8''".rawurlencode($file->nombre_original),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ];

        return $disk->response($file->ruta, $file->nombre_original, $headers);
    }
}
