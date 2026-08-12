<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $evaluationIds = DB::table('evaluaciones')->where('estado', 'EN_EVALUACION')->pluck('id');
        foreach ($evaluationIds as $evaluationId) {
            $affected = DB::table('evaluacion_descriptores')
                ->where('evaluacion_id', $evaluationId)
                ->whereNull('calificacion')
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('descriptor_archivos')->whereColumn('descriptor_archivos.evaluacion_descriptor_id', 'evaluacion_descriptores.id')->whereNull('descriptor_archivos.deleted_at'))
                ->update([
                    'estado' => 'EVALUADO',
                    'calificacion' => 0,
                    'calificacion_automatica' => true,
                    'motivo_calificacion' => 'ARCHIVO_NO_CARGADO',
                    'observacion_evaluador' => 'Calificación automática por cierre de la fase de carga sin archivo de evidencia.',
                    'evaluado_por' => null,
                    'evaluado_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($affected > 0) {
                DB::table('auditorias')->insert([
                    'user_id' => null,
                    'accion' => 'DESCRIPTORES_SIN_ARCHIVO_CALIFICADOS',
                    'tabla' => 'evaluacion_descriptores',
                    'registro_id' => $evaluationId,
                    'valores_anteriores' => null,
                    'valores_nuevos' => json_encode(['cantidad' => $affected, 'calificacion' => 0, 'motivo' => 'ARCHIVO_NO_CARGADO'], JSON_THROW_ON_ERROR),
                    'ip_address' => null,
                    'user_agent' => 'Migración etapa 8.5',
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // La calificación automática ya forma parte de la trazabilidad oficial y no se revierte.
    }
};
