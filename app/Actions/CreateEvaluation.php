<?php

namespace App\Actions;

use App\Enums\EstadoEvaluacion;
use App\Enums\EstadoEvaluacionDescriptor;
use App\Enums\EstadoEvaluacionDominio;
use App\Enums\EstadoModeloEvaluacion;
use App\Models\Evaluacion;
use App\Models\ModeloEvaluacion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateEvaluation
{
    /** @param array<string, mixed> $data */
    public function execute(array $data, User $creator): Evaluacion
    {
        return DB::transaction(function () use ($data, $creator): Evaluacion {
            $model = ModeloEvaluacion::query()->lockForUpdate()->with('dominios.criterios.descriptores')->findOrFail($data['modelo_evaluacion_id']);
            if ($model->estado !== EstadoModeloEvaluacion::Publicado) {
                throw ValidationException::withMessages(['modelo_evaluacion_id' => 'El instrumento seleccionado ya no está publicado.']);
            }

            $evaluation = Evaluacion::query()->create(collect($data)->except(['responsables', 'evaluadores'])->all() + ['estado' => EstadoEvaluacion::Borrador, 'creada_por' => $creator->id]);
            foreach ($model->dominios as $domain) {
                $evaluation->dominios()->create(['dominio_id' => $domain->id, 'responsable_id' => $data['responsables'][$domain->id], 'estado' => EstadoEvaluacionDominio::Pendiente]);
                foreach ($domain->criterios as $criterion) {
                    foreach ($criterion->descriptores as $descriptor) {
                        $evaluation->descriptores()->create(['descriptor_id' => $descriptor->id, 'estado' => EstadoEvaluacionDescriptor::Pendiente]);
                    }
                }
            }
            foreach (array_values($data['evaluadores']) as $index => $evaluatorId) {
                $evaluation->evaluadores()->attach($evaluatorId, ['es_principal' => $index === 0, 'asignado_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            }

            return $evaluation;
        });
    }
}
