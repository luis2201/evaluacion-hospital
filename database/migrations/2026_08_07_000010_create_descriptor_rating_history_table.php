<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluacion_descriptor_calificaciones', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('evaluacion_descriptor_id');
            $table->unsignedTinyInteger('calificacion_anterior')->nullable();
            $table->unsignedTinyInteger('calificacion_nueva');
            $table->text('observacion_anterior')->nullable();
            $table->text('observacion_nueva')->nullable();
            $table->unsignedBigInteger('calificada_por');
            $table->timestamp('calificada_at')->useCurrent();
            $table->index(['evaluacion_descriptor_id', 'calificada_at'], 'idx_eval_desc_calificacion_historial');
            $table->foreign('evaluacion_descriptor_id', 'fk_cal_hist_descriptor')->references('id')->on('evaluacion_descriptores')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('calificada_por', 'fk_cal_hist_usuario')->references('id')->on('users')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion_descriptor_calificaciones');
    }
};
