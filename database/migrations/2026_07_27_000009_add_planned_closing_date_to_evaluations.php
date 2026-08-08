<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluaciones', function (Blueprint $table): void {
            $table->date('fecha_cierre_prevista')->nullable()->after('fecha_inicio_evaluacion');
        });
    }

    public function down(): void
    {
        Schema::table('evaluaciones', function (Blueprint $table): void {
            $table->dropColumn('fecha_cierre_prevista');
        });
    }
};
