<?php

namespace App\Http\Controllers\Instrument;

use App\Enums\EstadoModeloEvaluacion;
use App\Http\Controllers\Controller;
use App\Models\ModeloEvaluacion;
use App\Services\AuditService;
use App\Services\InstrumentVersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InstrumentStateController extends Controller
{
    public function duplicate(Request $request, ModeloEvaluacion $instrumento, InstrumentVersionService $service, AuditService $audit): RedirectResponse
    {
        $this->authorize('replicate', $instrumento);
        $validated = $request->validate(['version' => ['required', 'string', 'max:30', Rule::unique('modelos_evaluacion')->where('nombre', $instrumento->nombre)]]);
        $copy = $service->duplicate($instrumento, $validated['version']);
        $audit->record('INSTRUMENTO_CLONADO', 'modelos_evaluacion', $copy->id, after: ['origen_id' => $instrumento->id, 'version' => $copy->version]);

        return redirect()->route('instruments.show', $copy)->with('status', 'Nueva versión borrador creada.');
    }

    public function publish(ModeloEvaluacion $instrumento, InstrumentVersionService $service, AuditService $audit): RedirectResponse
    {
        $this->authorize('publish', $instrumento);
        $service->publish($instrumento);
        $audit->recordModel('INSTRUMENTO_PUBLICADO', $instrumento->fresh());

        return back()->with('status', 'Instrumento publicado. Su estructura quedó bloqueada.');
    }

    public function archive(ModeloEvaluacion $instrumento, AuditService $audit): RedirectResponse
    {
        $this->authorize('archive', $instrumento);
        $instrumento->update(['estado' => EstadoModeloEvaluacion::Archivado]);
        $audit->recordModel('INSTRUMENTO_ARCHIVADO', $instrumento);

        return back()->with('status', 'Instrumento archivado correctamente.');
    }
}
