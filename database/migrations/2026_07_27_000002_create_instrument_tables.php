<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelos_evaluacion', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 180);
            $table->string('version', 30);
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['BORRADOR', 'PUBLICADO', 'ARCHIVADO'])->default('BORRADOR');
            $table->timestamp('publicado_at')->nullable();
            $table->timestamps();
            $table->unique(['nombre', 'version']);
        });

        Schema::create('dominios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('modelo_evaluacion_id')->constrained('modelos_evaluacion')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('codigo', 20);
            $table->string('nombre');
            $table->decimal('peso', 5, 2);
            $table->unsignedSmallInteger('orden');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['modelo_evaluacion_id', 'codigo']);
            $table->unique(['modelo_evaluacion_id', 'orden']);
        });

        Schema::create('criterios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dominio_id')->constrained('dominios')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('codigo', 20);
            $table->string('nombre');
            $table->unsignedSmallInteger('orden');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['dominio_id', 'codigo']);
            $table->unique(['dominio_id', 'orden']);
        });

        Schema::create('descriptores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('criterio_id')->constrained('criterios')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('codigo', 20);
            $table->text('descripcion');
            $table->unsignedSmallInteger('orden');
            $table->unsignedTinyInteger('puntaje_maximo')->default(2);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['criterio_id', 'codigo']);
            $table->unique(['criterio_id', 'orden']);
        });

        Schema::create('categorias_resultado', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('modelo_evaluacion_id')->constrained('modelos_evaluacion')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('nombre', 120);
            $table->decimal('porcentaje_desde', 5, 2);
            $table->decimal('porcentaje_hasta', 5, 2);
            $table->text('interpretacion')->nullable();
            $table->unsignedSmallInteger('orden');
            $table->timestamps();
            $table->unique(['modelo_evaluacion_id', 'orden']);
        });

        DB::statement('ALTER TABLE dominios ADD CONSTRAINT chk_dominios_peso CHECK (peso > 0 AND peso <= 100)');
        DB::statement('ALTER TABLE descriptores ADD CONSTRAINT chk_descriptor_puntaje_maximo CHECK (puntaje_maximo = 2)');
        DB::statement('ALTER TABLE categorias_resultado ADD CONSTRAINT chk_categoria_rango CHECK (porcentaje_desde >= 0 AND porcentaje_hasta <= 100 AND porcentaje_desde <= porcentaje_hasta)');
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_resultado');
        Schema::dropIfExists('descriptores');
        Schema::dropIfExists('criterios');
        Schema::dropIfExists('dominios');
        Schema::dropIfExists('modelos_evaluacion');
    }
};
