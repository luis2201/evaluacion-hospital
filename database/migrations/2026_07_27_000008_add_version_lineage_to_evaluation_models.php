<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modelos_evaluacion', function (Blueprint $table): void {
            $table->foreignId('modelo_origen_id')->nullable()->after('id')->constrained('modelos_evaluacion')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('modelos_evaluacion', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('modelo_origen_id');
        });
    }
};
