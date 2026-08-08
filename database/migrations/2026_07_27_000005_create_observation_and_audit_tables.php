<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evaluacion_descriptor_id')->constrained('evaluacion_descriptores')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('creada_por')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('asunto');
            $table->text('detalle');
            $table->enum('estado', ['ABIERTA', 'RESPONDIDA', 'CERRADA'])->default('ABIERTA');
            $table->date('fecha_limite')->nullable();
            $table->foreignId('cerrada_por')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('cerrada_at')->nullable();
            $table->timestamps();
            $table->index('evaluacion_descriptor_id');
            $table->index('estado');
        });

        Schema::create('observacion_respuestas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('observacion_id')->constrained('observaciones')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('respondida_por')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->text('respuesta');
            $table->timestamps();
        });

        Schema::create('auditorias', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('accion', 60);
            $table->string('tabla', 100);
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['tabla', 'registro_id']);
            $table->index('user_id');
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('auditorias');
        Schema::dropIfExists('observacion_respuestas');
        Schema::dropIfExists('observaciones');
    }
};
