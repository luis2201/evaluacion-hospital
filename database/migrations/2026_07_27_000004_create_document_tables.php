<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descriptor_archivos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evaluacion_descriptor_id')->constrained('evaluacion_descriptores')->cascadeOnUpdate()->cascadeOnDelete();
            $table->text('descripcion')->nullable();
            $table->string('disco', 50)->default('private');
            $table->string('ruta', 1000);
            $table->string('nombre_original');
            $table->string('nombre_almacenado');
            $table->string('mime_type', 150);
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('tamano_bytes');
            $table->char('hash_sha256', 64);
            $table->foreignId('cargado_por')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->index('evaluacion_descriptor_id');
            $table->unique(['evaluacion_descriptor_id', 'hash_sha256']);
        });

        Schema::create('descriptor_enlaces', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evaluacion_descriptor_id')->constrained('evaluacion_descriptores')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('descripcion', 500)->nullable();
            $table->foreignId('registrado_por')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->index('evaluacion_descriptor_id');
        });

        Schema::create('descriptor_archivo_descargas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('descriptor_archivo_id')->constrained('descriptor_archivos')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('descargado_at')->useCurrent();
            $table->index('descriptor_archivo_id');
            $table->index('user_id');
        });

        DB::statement('ALTER TABLE descriptor_archivos ADD CONSTRAINT chk_descriptor_archivo_tamano CHECK (tamano_bytes > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('descriptor_archivo_descargas');
        Schema::dropIfExists('descriptor_enlaces');
        Schema::dropIfExists('descriptor_archivos');
    }
};
