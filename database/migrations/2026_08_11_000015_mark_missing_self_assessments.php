<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE autoevaluaciones_dominios MODIFY estado ENUM('BORRADOR','ENVIADA','INCUMPLIDA') NOT NULL DEFAULT 'BORRADOR'");
        DB::statement("ALTER TABLE evaluacion_dominios MODIFY estado ENUM('PENDIENTE','EN_CARGA','ENVIADO','OBSERVADO','COMPLETO','INCUMPLIDO','CERRADO') NOT NULL DEFAULT 'PENDIENTE'");

        DB::unprepared('DROP TRIGGER IF EXISTS trg_evaluacion_no_cerrar_incompleta');
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_evaluacion_no_cerrar_incompleta
BEFORE UPDATE ON evaluaciones FOR EACH ROW
BEGIN
    DECLARE v_total_modelo INT DEFAULT 0;
    DECLARE v_total_dominios INT DEFAULT 0;
    DECLARE v_total_instanciados INT DEFAULT 0;
    DECLARE v_pendientes INT DEFAULT 0;
    DECLARE v_autoevaluaciones_finalizadas INT DEFAULT 0;

    IF NEW.estado = 'CERRADA' AND OLD.estado <> 'CERRADA' THEN
        SELECT COUNT(*) INTO v_total_modelo FROM descriptores de
          JOIN criterios c ON c.id = de.criterio_id JOIN dominios d ON d.id = c.dominio_id
         WHERE d.modelo_evaluacion_id = NEW.modelo_evaluacion_id AND d.activo = TRUE AND c.activo = TRUE AND de.activo = TRUE;
        SELECT COUNT(*), SUM(CASE WHEN calificacion IS NULL THEN 1 ELSE 0 END)
          INTO v_total_instanciados, v_pendientes FROM evaluacion_descriptores WHERE evaluacion_id = NEW.id;
        SELECT COUNT(*) INTO v_total_dominios FROM dominios
         WHERE modelo_evaluacion_id = NEW.modelo_evaluacion_id AND activo = TRUE;
        SELECT COUNT(*) INTO v_autoevaluaciones_finalizadas FROM autoevaluaciones_dominios ad
          JOIN evaluacion_dominios evd ON evd.id = ad.evaluacion_dominio_id
         WHERE evd.evaluacion_id = NEW.id AND ad.estado IN ('ENVIADA', 'INCUMPLIDA');

        IF v_total_instanciados <> v_total_modelo OR v_pendientes > 0 OR v_autoevaluaciones_finalizadas <> v_total_dominios THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede cerrar: existen ítems sin resultado final o autoevaluaciones sin clasificar.';
        END IF;
    END IF;
END
SQL);

        $domains = DB::table('evaluacion_dominios as ed')
            ->join('evaluaciones as e', 'e.id', '=', 'ed.evaluacion_id')
            ->where('e.estado', 'EN_EVALUACION')
            ->select('ed.id', 'ed.responsable_id')
            ->get();
        foreach ($domains as $domain) {
            $selfAssessment = DB::table('autoevaluaciones_dominios')->where('evaluacion_dominio_id', $domain->id)->first();
            if (! $selfAssessment) {
                DB::table('autoevaluaciones_dominios')->insert([
                    'evaluacion_dominio_id' => $domain->id,
                    'contenido' => 'Autoevaluación no enviada dentro del plazo de carga establecido.',
                    'cantidad_palabras' => 9,
                    'estado' => 'INCUMPLIDA',
                    'registrada_por' => $domain->responsable_id,
                    'enviada_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($selfAssessment->estado !== 'ENVIADA') {
                DB::table('autoevaluaciones_dominios')->where('id', $selfAssessment->id)->update([
                    'estado' => 'INCUMPLIDA',
                    'enviada_at' => null,
                    'updated_at' => now(),
                ]);
            }
        }
        DB::statement("UPDATE evaluacion_dominios ed JOIN autoevaluaciones_dominios ad ON ad.evaluacion_dominio_id = ed.id SET ed.estado = 'INCUMPLIDO' WHERE ad.estado = 'INCUMPLIDA'");
    }

    public function down(): void
    {
        // Los incumplimientos registrados forman parte de la trazabilidad y no se eliminan.
    }
};
