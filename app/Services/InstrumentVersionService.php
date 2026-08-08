<?php

namespace App\Services;

use App\Enums\EstadoModeloEvaluacion;
use App\Models\ModeloEvaluacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InstrumentVersionService
{
    public function duplicate(ModeloEvaluacion $source, string $version): ModeloEvaluacion
    {
        return DB::transaction(function () use ($source, $version): ModeloEvaluacion {
            $source->load('dominios.criterios.descriptores', 'categoriasResultado');
            $copy = ModeloEvaluacion::query()->create([
                'modelo_origen_id' => $source->id,
                'nombre' => $source->nombre, 'version' => $version,
                'descripcion' => $source->descripcion, 'estado' => EstadoModeloEvaluacion::Borrador,
            ]);

            foreach ($source->dominios as $domain) {
                $domainCopy = $copy->dominios()->create($domain->only(['codigo', 'nombre', 'peso', 'orden', 'activo']));
                foreach ($domain->criterios as $criterion) {
                    $criterionCopy = $domainCopy->criterios()->create($criterion->only(['codigo', 'nombre', 'orden', 'activo']));
                    foreach ($criterion->descriptores as $descriptor) {
                        $criterionCopy->descriptores()->create($descriptor->only(['codigo', 'descripcion', 'orden', 'puntaje_maximo', 'activo']));
                    }
                }
            }

            foreach ($source->categoriasResultado as $category) {
                $copy->categoriasResultado()->create($category->only(['nombre', 'porcentaje_desde', 'porcentaje_hasta', 'interpretacion', 'orden']));
            }

            return $copy;
        });
    }

    public function publish(ModeloEvaluacion $model): void
    {
        $model->load('dominios.criterios.descriptores', 'categoriasResultado');
        $errors = [];

        if ($model->dominios->isEmpty()) {
            $errors[] = 'Debe existir al menos un dominio.';
        }
        if (round((float) $model->dominios->sum('peso'), 2) !== 100.0) {
            $errors[] = 'Los pesos de los dominios deben sumar exactamente 100 %.';
        }

        foreach ($model->dominios as $domain) {
            if ($domain->criterios->isEmpty()) {
                $errors[] = "El dominio {$domain->codigo} no tiene criterios.";
            }
            foreach ($domain->criterios as $criterion) {
                if ($criterion->descriptores->isEmpty()) {
                    $errors[] = "El criterio {$domain->codigo}.{$criterion->codigo} no tiene descriptores.";
                }
            }
        }

        $categories = $model->categoriasResultado->sortBy('porcentaje_desde')->values();
        if ($categories->isEmpty() || (float) $categories->first()?->porcentaje_desde !== 0.0 || (float) $categories->last()?->porcentaje_hasta !== 100.0) {
            $errors[] = 'Las categorías deben cubrir desde 0 hasta 100 %.';
        }
        foreach ($categories->zip($categories->skip(1)) as [$current, $next]) {
            if ($next && round((float) $next->porcentaje_desde - (float) $current->porcentaje_hasta, 2) !== 0.01) {
                $errors[] = 'Las categorías no pueden tener vacíos ni superposiciones.';
                break;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['instrumento' => $errors]);
        }

        $model->update(['estado' => EstadoModeloEvaluacion::Publicado, 'publicado_at' => now()]);
    }
}
