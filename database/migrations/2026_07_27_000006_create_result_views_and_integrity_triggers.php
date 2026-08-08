<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW vw_resultados_criterios AS
SELECT ed.evaluacion_id, c.id AS criterio_id, c.dominio_id,
       c.codigo AS criterio_codigo, c.nombre AS criterio_nombre,
       COUNT(ed.id) AS total_descriptores,
       SUM(CASE WHEN ed.calificacion IS NOT NULL THEN 1 ELSE 0 END) AS descriptores_calificados,
       SUM(CASE WHEN ed.calificacion IS NULL THEN 1 ELSE 0 END) AS descriptores_pendientes,
       COALESCE(SUM(ed.calificacion), 0) AS puntos_obtenidos,
       SUM(de.puntaje_maximo) AS puntos_maximos,
       ROUND(COALESCE(SUM(ed.calificacion), 0) / NULLIF(SUM(de.puntaje_maximo), 0) * 100, 2) AS porcentaje_cumplimiento_provisional,
       ROUND(SUM(CASE WHEN ed.calificacion IS NOT NULL THEN 1 ELSE 0 END) / NULLIF(COUNT(ed.id), 0) * 100, 2) AS porcentaje_avance,
       CASE
           WHEN SUM(CASE WHEN ed.calificacion IS NOT NULL THEN 1 ELSE 0 END) = 0 THEN 'PENDIENTE'
           WHEN SUM(CASE WHEN ed.calificacion IS NULL THEN 1 ELSE 0 END) = 0 THEN 'COMPLETO'
           ELSE 'EN_EVALUACION'
       END AS estado_calculo
FROM evaluacion_descriptores ed
JOIN descriptores de ON de.id = ed.descriptor_id
JOIN criterios c ON c.id = de.criterio_id
GROUP BY ed.evaluacion_id, c.id, c.dominio_id, c.codigo, c.nombre
SQL);

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW vw_resultados_dominios AS
SELECT ed.evaluacion_id, d.id AS dominio_id, d.codigo AS dominio_codigo,
       d.nombre AS dominio_nombre, d.peso,
       COUNT(DISTINCT c.id) AS total_criterios,
       COUNT(ed.id) AS total_descriptores,
       SUM(CASE WHEN ed.calificacion IS NOT NULL THEN 1 ELSE 0 END) AS descriptores_calificados,
       SUM(CASE WHEN ed.calificacion IS NULL THEN 1 ELSE 0 END) AS descriptores_pendientes,
       COALESCE(SUM(ed.calificacion), 0) AS puntos_obtenidos,
       SUM(de.puntaje_maximo) AS puntos_maximos,
       ROUND(COALESCE(SUM(ed.calificacion), 0) / NULLIF(SUM(de.puntaje_maximo), 0) * 100, 2) AS porcentaje_cumplimiento_provisional,
       ROUND(SUM(CASE WHEN ed.calificacion IS NOT NULL THEN 1 ELSE 0 END) / NULLIF(COUNT(ed.id), 0) * 100, 2) AS porcentaje_avance,
       ROUND(COALESCE(SUM(ed.calificacion), 0) / NULLIF(SUM(de.puntaje_maximo), 0) * d.peso, 2) AS aporte_ponderado_provisional,
       CASE
           WHEN SUM(CASE WHEN ed.calificacion IS NOT NULL THEN 1 ELSE 0 END) = 0 THEN 'PENDIENTE'
           WHEN SUM(CASE WHEN ed.calificacion IS NULL THEN 1 ELSE 0 END) = 0 THEN 'COMPLETO'
           ELSE 'EN_EVALUACION'
       END AS estado_calculo
FROM evaluacion_descriptores ed
JOIN descriptores de ON de.id = ed.descriptor_id
JOIN criterios c ON c.id = de.criterio_id
JOIN dominios d ON d.id = c.dominio_id
GROUP BY ed.evaluacion_id, d.id, d.codigo, d.nombre, d.peso
SQL);

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW vw_resultados_generales AS
SELECT r.evaluacion_id, r.codigo, r.nombre, r.estado, r.dominios_con_resultado,
       r.total_descriptores, r.descriptores_calificados, r.descriptores_pendientes,
       r.porcentaje_avance, r.puntaje_provisional,
       CASE WHEN r.descriptores_pendientes > 0 THEN 'INCOMPLETA' ELSE 'COMPLETA' END AS estado_calculo,
       CASE WHEN r.descriptores_pendientes > 0 THEN NULL ELSE cr.nombre END AS categoria_final
FROM (
    SELECT e.id AS evaluacion_id, e.modelo_evaluacion_id, e.codigo, e.nombre, e.estado,
           COUNT(rd.dominio_id) AS dominios_con_resultado,
           SUM(rd.total_descriptores) AS total_descriptores,
           SUM(rd.descriptores_calificados) AS descriptores_calificados,
           SUM(rd.descriptores_pendientes) AS descriptores_pendientes,
           ROUND(SUM(rd.descriptores_calificados) / NULLIF(SUM(rd.total_descriptores), 0) * 100, 2) AS porcentaje_avance,
           ROUND(SUM(rd.aporte_ponderado_provisional), 2) AS puntaje_provisional
    FROM evaluaciones e
    JOIN vw_resultados_dominios rd ON rd.evaluacion_id = e.id
    GROUP BY e.id, e.modelo_evaluacion_id, e.codigo, e.nombre, e.estado
) r
LEFT JOIN categorias_resultado cr
  ON cr.modelo_evaluacion_id = r.modelo_evaluacion_id
 AND r.puntaje_provisional BETWEEN cr.porcentaje_desde AND cr.porcentaje_hasta
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_evaluacion_no_cerrar_incompleta
BEFORE UPDATE ON evaluaciones FOR EACH ROW
BEGIN
    DECLARE v_total_modelo INT DEFAULT 0;
    DECLARE v_total_dominios INT DEFAULT 0;
    DECLARE v_total_instanciados INT DEFAULT 0;
    DECLARE v_pendientes INT DEFAULT 0;
    DECLARE v_autoevaluaciones_enviadas INT DEFAULT 0;

    IF NEW.estado = 'CERRADA' AND OLD.estado <> 'CERRADA' THEN
        SELECT COUNT(*) INTO v_total_modelo
          FROM descriptores de
          JOIN criterios c ON c.id = de.criterio_id
          JOIN dominios d ON d.id = c.dominio_id
         WHERE d.modelo_evaluacion_id = NEW.modelo_evaluacion_id
           AND d.activo = TRUE AND c.activo = TRUE AND de.activo = TRUE;

        SELECT COUNT(*), SUM(CASE WHEN calificacion IS NULL THEN 1 ELSE 0 END)
          INTO v_total_instanciados, v_pendientes
          FROM evaluacion_descriptores WHERE evaluacion_id = NEW.id;

        SELECT COUNT(*) INTO v_total_dominios FROM dominios
         WHERE modelo_evaluacion_id = NEW.modelo_evaluacion_id AND activo = TRUE;

        SELECT COUNT(*) INTO v_autoevaluaciones_enviadas
          FROM autoevaluaciones_dominios ad
          JOIN evaluacion_dominios evd ON evd.id = ad.evaluacion_dominio_id
         WHERE evd.evaluacion_id = NEW.id AND ad.estado = 'ENVIADA';

        IF v_total_instanciados <> v_total_modelo OR v_pendientes > 0
           OR v_autoevaluaciones_enviadas <> v_total_dominios THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'No se puede cerrar: faltan descriptores calificados, ítems del modelo o autoevaluaciones enviadas.';
        END IF;
    END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_no_modificar_calificacion_cerrada
BEFORE UPDATE ON evaluacion_descriptores FOR EACH ROW
BEGIN
    DECLARE v_estado VARCHAR(30);
    DECLARE v_archivos INT DEFAULT 0;

    SELECT estado INTO v_estado FROM evaluaciones WHERE id = NEW.evaluacion_id;

    IF v_estado = 'CERRADA' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No se puede modificar una calificación de una evaluación cerrada.';
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

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_no_cargar_archivo_evaluacion_cerrada
BEFORE INSERT ON descriptor_archivos FOR EACH ROW
BEGIN
    DECLARE v_estado VARCHAR(30);
    SELECT e.estado INTO v_estado
      FROM evaluacion_descriptores ed JOIN evaluaciones e ON e.id = ed.evaluacion_id
     WHERE ed.id = NEW.evaluacion_descriptor_id;
    IF v_estado IN ('CERRADA', 'CANCELADA') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No se pueden cargar archivos en una evaluación cerrada o cancelada.';
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
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'No se puede eliminar evidencia de un descriptor calificado o de una evaluación cerrada.';
        END IF;
    END IF;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_no_eliminar_archivo_calificado');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_no_cargar_archivo_evaluacion_cerrada');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_no_modificar_calificacion_cerrada');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_evaluacion_no_cerrar_incompleta');
        DB::unprepared('DROP VIEW IF EXISTS vw_resultados_generales');
        DB::unprepared('DROP VIEW IF EXISTS vw_resultados_dominios');
        DB::unprepared('DROP VIEW IF EXISTS vw_resultados_criterios');
    }
};
