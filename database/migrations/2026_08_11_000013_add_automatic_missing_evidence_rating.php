<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('evaluacion_descriptores', 'calificacion_automatica')) {
            Schema::table('evaluacion_descriptores', function (Blueprint $table): void {
                $table->boolean('calificacion_automatica')->default(false)->after('calificacion');
                $table->string('motivo_calificacion', 100)->nullable()->after('calificacion_automatica');
            });
        }

        $hasScheduleConstraint = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'evaluaciones')
            ->where('CONSTRAINT_NAME', 'chk_evaluacion_cronograma')
            ->exists();
        if (! $hasScheduleConstraint) {
            DB::statement('ALTER TABLE evaluaciones ADD CONSTRAINT chk_evaluacion_cronograma CHECK (fecha_inicio IS NULL OR fecha_limite_carga IS NULL OR fecha_inicio_evaluacion IS NULL OR fecha_cierre_prevista IS NULL OR (fecha_inicio <= fecha_limite_carga AND fecha_limite_carga < fecha_inicio_evaluacion AND fecha_inicio_evaluacion < fecha_cierre_prevista))');
        }
        DB::unprepared('DROP TRIGGER IF EXISTS trg_no_modificar_calificacion_cerrada');
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_no_modificar_calificacion_cerrada
BEFORE UPDATE ON evaluacion_descriptores FOR EACH ROW
BEGIN
    DECLARE v_estado VARCHAR(30);
    DECLARE v_archivos INT DEFAULT 0;
    SELECT estado INTO v_estado FROM evaluaciones WHERE id = NEW.evaluacion_id;

    IF v_estado IN ('CERRADA', 'CANCELADA')
       AND (NOT (NEW.calificacion <=> OLD.calificacion)
            OR NOT (NEW.calificacion_automatica <=> OLD.calificacion_automatica)
            OR NOT (NEW.motivo_calificacion <=> OLD.motivo_calificacion)
            OR NOT (NEW.observacion_evaluador <=> OLD.observacion_evaluador)
            OR NOT (NEW.evaluado_por <=> OLD.evaluado_por)
            OR NOT (NEW.evaluado_at <=> OLD.evaluado_at)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede modificar una calificación de una evaluación finalizada.';
    END IF;

    IF NEW.calificacion IS NOT NULL THEN
        SELECT COUNT(*) INTO v_archivos FROM descriptor_archivos
         WHERE evaluacion_descriptor_id = NEW.id AND deleted_at IS NULL;
        IF v_archivos = 0 AND NOT (NEW.calificacion = 0 AND NEW.calificacion_automatica = TRUE AND NEW.motivo_calificacion = 'ARCHIVO_NO_CARGADO') THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede calificar un descriptor sin evidencia, salvo el cero automático por archivo no cargado.';
        END IF;
    END IF;
END
SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE evaluaciones DROP CHECK chk_evaluacion_cronograma');
        Schema::table('evaluacion_descriptores', function (Blueprint $table): void {
            $table->dropColumn(['calificacion_automatica', 'motivo_calificacion']);
        });
    }
};
