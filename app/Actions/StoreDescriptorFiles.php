<?php

namespace App\Actions;

use App\Enums\EstadoEvaluacion;
use App\Enums\EstadoEvaluacionDescriptor;
use App\Models\DescriptorArchivo;
use App\Models\Evaluacion;
use App\Models\EvaluacionDescriptor;
use App\Models\User;
use App\Services\AuditService;
use App\Services\EvaluationCalendarService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StoreDescriptorFiles
{
    public function __construct(private readonly AuditService $audit, private readonly EvaluationCalendarService $calendar) {}

    /** @param array<int, UploadedFile> $files */
    public function execute(Evaluacion $evaluation, EvaluacionDescriptor $evaluationDescriptor, User $user, array $files, ?string $description): int
    {
        $isRemediation = $evaluation->estado === EstadoEvaluacion::EnEvaluacion
            && $evaluationDescriptor->estado === EstadoEvaluacionDescriptor::Observado
            && $evaluationDescriptor->observaciones()->where('estado', 'ABIERTA')->exists();
        if (! $this->calendar->isLoadingOpen($evaluation) && ! $isRemediation) {
            throw ValidationException::withMessages(['archivos' => 'La evaluación no se encuentra en la fase de carga de evidencias.']);
        }

        $storedPaths = [];

        try {
            return DB::transaction(function () use ($evaluation, $evaluationDescriptor, $user, $files, $description, &$storedPaths): int {
                $lockedDescriptor = EvaluacionDescriptor::query()->lockForUpdate()->findOrFail($evaluationDescriptor->id);
                abort_unless($lockedDescriptor->evaluacion_id === $evaluation->id, 404);

                $prepared = collect($files)->map(function (UploadedFile $file): array {
                    return ['file' => $file, 'hash' => hash_file('sha256', $file->getRealPath())];
                });

                if ($prepared->pluck('hash')->duplicates()->isNotEmpty()) {
                    throw ValidationException::withMessages(['archivos' => 'La selección contiene archivos duplicados.']);
                }

                $saved = 0;
                foreach ($prepared as $item) {
                    $file = $item['file'];
                    $hash = $item['hash'];
                    $existing = DescriptorArchivo::withTrashed()
                        ->where('evaluacion_descriptor_id', $lockedDescriptor->id)
                        ->where('hash_sha256', $hash)
                        ->first();

                    if ($existing && ! $existing->trashed()) {
                        throw ValidationException::withMessages(['archivos' => "El archivo {$file->getClientOriginalName()} ya fue cargado en este descriptor."]);
                    }

                    if ($existing?->trashed()) {
                        $existing->restore();
                        $existing->update(['eliminado_por' => null, 'descripcion' => $description, 'cargado_por' => $user->id]);
                        $this->audit->recordModel('EVIDENCIA_ARCHIVO_RESTAURADO', $existing);
                        $saved++;

                        continue;
                    }

                    $extension = mb_strtolower($file->getClientOriginalExtension() ?: 'dat');
                    $storedName = Str::uuid().'.'.$extension;
                    $path = "evaluaciones/{$evaluation->id}/descriptores/{$lockedDescriptor->id}/{$storedName}";
                    Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));
                    $storedPaths[] = $path;

                    $evidence = $lockedDescriptor->archivos()->create([
                        'descripcion' => $description,
                        'disco' => 'local',
                        'ruta' => $path,
                        'nombre_original' => $file->getClientOriginalName(),
                        'nombre_almacenado' => $storedName,
                        'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                        'extension' => $extension,
                        'tamano_bytes' => $file->getSize(),
                        'hash_sha256' => $hash,
                        'cargado_por' => $user->id,
                    ]);
                    $this->audit->recordModel('EVIDENCIA_ARCHIVO_CARGADO', $evidence);
                    $saved++;
                }

                return $saved;
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }
}
