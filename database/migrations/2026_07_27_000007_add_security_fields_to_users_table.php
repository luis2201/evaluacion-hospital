<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('ultimo_acceso_at')->nullable()->after('activo');
            $table->timestamp('password_changed_at')->nullable()->after('ultimo_acceso_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['ultimo_acceso_at', 'password_changed_at']);
        });
    }
};
