<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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
            OR NOT (NEW.observacion_evaluador <=> OLD.observacion_evaluador)
            OR NOT (NEW.evaluado_por <=> OLD.evaluado_por)
            OR NOT (NEW.evaluado_at <=> OLD.evaluado_at)) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No se puede modificar una calificación de una evaluación finalizada.';
    END IF;

    IF NEW.calificacion IS NOT NULL THEN
        SELECT COUNT(*) INTO v_archivos FROM descriptor_archivos
         WHERE evaluacion_descriptor_id = NEW.id AND deleted_at IS NULL;
        IF v_archivos = 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'No se puede calificar un descriptor sin al menos un archivo de evidencia.';
        END IF;
    END IF;
END
SQL);

        DB::unprepared('DROP TRIGGER IF EXISTS trg_no_eliminar_archivo_calificado');
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_no_eliminar_archivo_calificado
BEFORE UPDATE ON descriptor_archivos FOR EACH ROW
BEGIN
    DECLARE v_calificacion TINYINT UNSIGNED;
    DECLARE v_estado VARCHAR(30);

    IF NOT (OLD.deleted_at <=> NEW.deleted_at) THEN
        SELECT ed.calificacion, e.estado INTO v_calificacion, v_estado
          FROM evaluacion_descriptores ed JOIN evaluaciones e ON e.id = ed.evaluacion_id
         WHERE ed.id = NEW.evaluacion_descriptor_id;

        IF v_estado IN ('CERRADA', 'CANCELADA') THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'No se puede retirar ni restaurar evidencia de una evaluación finalizada.';
        END IF;

        IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL AND v_calificacion IS NOT NULL THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'No se puede eliminar evidencia de un descriptor calificado.';
        END IF;
    END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_no_insertar_enlace_evaluacion_finalizada
BEFORE INSERT ON descriptor_enlaces FOR EACH ROW
BEGIN
    DECLARE v_estado VARCHAR(30);
    SELECT e.estado INTO v_estado
      FROM evaluacion_descriptores ed JOIN evaluaciones e ON e.id = ed.evaluacion_id
     WHERE ed.id = NEW.evaluacion_descriptor_id;
    IF v_estado IN ('CERRADA', 'CANCELADA') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No se pueden registrar enlaces en una evaluación finalizada.';
    END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_no_cambiar_enlace_evaluacion_finalizada
BEFORE UPDATE ON descriptor_enlaces FOR EACH ROW
BEGIN
    DECLARE v_estado VARCHAR(30);
    SELECT e.estado INTO v_estado
      FROM evaluacion_descriptores ed JOIN evaluaciones e ON e.id = ed.evaluacion_id
     WHERE ed.id = NEW.evaluacion_descriptor_id;
    IF v_estado IN ('CERRADA', 'CANCELADA') AND NOT (OLD.deleted_at <=> NEW.deleted_at) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No se puede retirar ni restaurar un enlace de una evaluación finalizada.';
    END IF;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_no_cambiar_enlace_evaluacion_finalizada');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_no_insertar_enlace_evaluacion_finalizada');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_no_eliminar_archivo_calificado');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_no_modificar_calificacion_cerrada');

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_no_modificar_calificacion_cerrada
BEFORE UPDATE ON evaluacion_descriptores FOR EACH ROW
BEGIN
    DECLARE v_estado VARCHAR(30);
    DECLARE v_archivos INT DEFAULT 0;
    SELECT estado INTO v_estado FROM evaluaciones WHERE id = NEW.evaluacion_id;
    IF v_estado = 'CERRADA' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede modificar una calificación de una evaluación cerrada.';
    END IF;
    IF NEW.calificacion IS NOT NULL THEN
        SELECT COUNT(*) INTO v_archivos FROM descriptor_archivos WHERE evaluacion_descriptor_id = NEW.id AND deleted_at IS NULL;
        IF v_archivos = 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede calificar un descriptor sin al menos un archivo de evidencia.';
        END IF;
    END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_no_eliminar_archivo_calificado
BEFORE UPDATE ON descriptor_archivos FOR EACH ROW
BEGIN
    DECLARE v_calificacion TINYINT UNSIGNED;
    DECLARE v_estado VARCHAR(30);
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
        SELECT ed.calificacion, e.estado INTO v_calificacion, v_estado
          FROM evaluacion_descriptores ed JOIN evaluaciones e ON e.id = ed.evaluacion_id
         WHERE ed.id = NEW.evaluacion_descriptor_id;
        IF v_calificacion IS NOT NULL OR v_estado IN ('CERRADA', 'CANCELADA') THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede eliminar evidencia de un descriptor calificado o de una evaluación cerrada.';
        END IF;
    END IF;
END
SQL);
    }
};
