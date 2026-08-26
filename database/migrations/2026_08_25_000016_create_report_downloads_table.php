<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporte_descargas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evaluacion_id')->constrained('evaluaciones')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('tipo', 40);
            $table->string('nombre_archivo');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('descargado_at')->useCurrent();
            $table->index(['evaluacion_id', 'descargado_at']);
            $table->index(['user_id', 'descargado_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporte_descargas');
    }
};
