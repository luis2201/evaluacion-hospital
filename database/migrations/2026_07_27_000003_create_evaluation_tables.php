<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('modelo_evaluacion_id')->constrained('modelos_evaluacion')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->enum('tipo_escenario', ['CLINICA', 'QUIRURGICA', 'MIXTA', 'OTRA'])->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_limite_carga')->nullable();
            $table->date('fecha_inicio_evaluacion')->nullable();
            $table->date('fecha_cierre')->nullable();
            $table->enum('estado', ['BORRADOR', 'CARGA_EVIDENCIAS', 'EN_EVALUACION', 'CERRADA', 'CANCELADA'])->default('BORRADOR')->index();
            $table->foreignId('creada_por')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('cerrada_por')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('cerrada_at')->nullable();
            $table->timestamps();
        });

        Schema::create('evaluacion_dominios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evaluacion_id')->constrained('evaluaciones')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('dominio_id')->constrained('dominios')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('responsable_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('estado', ['PENDIENTE', 'EN_CARGA', 'ENVIADO', 'OBSERVADO', 'COMPLETO', 'CERRADO'])->default('PENDIENTE');
            $table->timestamp('enviado_at')->nullable();
            $table->timestamp('completado_at')->nullable();
            $table->timestamps();
            $table->unique(['evaluacion_id', 'dominio_id']);
            $table->index('responsable_id');
        });

        Schema::create('autoevaluaciones_dominios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evaluacion_dominio_id')->unique()->constrained('evaluacion_dominios')->cascadeOnUpdate()->cascadeOnDelete();
            $table->text('contenido');
            $table->unsignedSmallInteger('cantidad_palabras');
            $table->enum('estado', ['BORRADOR', 'ENVIADA'])->default('BORRADOR');
            $table->foreignId('registrada_por')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamp('enviada_at')->nullable();
            $table->timestamps();
        });

        Schema::create('evaluacion_evaluadores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evaluacion_id')->constrained('evaluaciones')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('evaluador_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->boolean('es_principal')->default(true);
            $table->timestamp('asignado_at')->useCurrent();
            $table->timestamps();
            $table->unique(['evaluacion_id', 'evaluador_id']);
        });

        Schema::create('evaluacion_descriptores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evaluacion_id')->constrained('evaluaciones')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('descriptor_id')->constrained('descriptores')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('estado', ['PENDIENTE', 'EN_EVALUACION', 'OBSERVADO', 'EVALUADO'])->default('PENDIENTE');
            $table->unsignedTinyInteger('calificacion')->nullable();
            $table->text('observacion_evaluador')->nullable();
            $table->foreignId('evaluado_por')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('evaluado_at')->nullable();
            $table->timestamps();
            $table->unique(['evaluacion_id', 'descriptor_id']);
            $table->index(['evaluacion_id', 'estado']);
            $table->index(['evaluacion_id', 'calificacion']);
        });

        DB::statement('ALTER TABLE autoevaluaciones_dominios ADD CONSTRAINT chk_autoevaluacion_palabras CHECK (cantidad_palabras <= 250)');
        DB::statement('ALTER TABLE evaluacion_descriptores ADD CONSTRAINT chk_eval_desc_calificacion CHECK (calificacion IS NULL OR calificacion IN (0,1,2))');
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion_descriptores');
        Schema::dropIfExists('evaluacion_evaluadores');
        Schema::dropIfExists('autoevaluaciones_dominios');
        Schema::dropIfExists('evaluacion_dominios');
        Schema::dropIfExists('evaluaciones');
    }
};
