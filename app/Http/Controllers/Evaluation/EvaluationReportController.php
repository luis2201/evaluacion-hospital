<?php

namespace App\Http\Controllers\Evaluation;

use App\Http\Controllers\Controller;
use App\Models\Evaluacion;
use App\Models\ReporteDescarga;
use App\Services\AuditService;
use App\Services\EvaluationResultService;
use App\Services\SystemSettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EvaluationReportController extends Controller
{
    public function download(Request $request, Evaluacion $evaluacion, EvaluationResultService $results, SystemSettingsService $settings, AuditService $audit): Response
    {
        $this->authorize('viewResults', $evaluacion);
        $evaluacion->load(['modeloEvaluacion', 'cerrador', 'dominios.dominio', 'dominios.autoevaluacion']);
        $descriptors = $evaluacion->descriptores()->with(['descriptor.criterio.dominio', 'evaluador', 'archivos', 'observaciones.respuestas'])->get()
            ->sortBy(fn ($item) => [$item->descriptor->criterio->dominio->orden, $item->descriptor->criterio->orden, $item->descriptor->orden]);
        $filename = 'resultado-'.str($evaluacion->codigo)->slug().'.pdf';
        $data = ['evaluacion' => $evaluacion, 'descriptors' => $descriptors, 'institution' => $settings->get('institution_name'), 'generatedAt' => now()] + $results->for($evaluacion);

        ReporteDescarga::query()->create([
            'evaluacion_id' => $evaluacion->id, 'user_id' => $request->user()->id, 'tipo' => 'RESULTADO_GENERAL_PDF',
            'nombre_archivo' => $filename, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'descargado_at' => now(),
        ]);
        $audit->record('REPORTE_RESULTADOS_DESCARGADO', 'evaluaciones', $evaluacion->id, after: ['tipo' => 'PDF', 'archivo' => $filename]);

        return Pdf::loadView('reports.evaluation-results', $data)->setPaper('a4')->download($filename);
    }
}
