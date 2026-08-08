<?php

namespace App\Http\Controllers\Instrument;

use App\Http\Controllers\Controller;
use App\Models\Criterio;
use App\Models\Dominio;
use App\Models\ModeloEvaluacion;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CriterionController extends Controller
{
    public function store(Request $request, ModeloEvaluacion $instrumento, Dominio $dominio, AuditService $audit): RedirectResponse
    {
        $this->guard($instrumento, $dominio);
        $criterion = $dominio->criterios()->create($this->data($request, $dominio));
        $audit->recordModel('CRITERIO_CREADO', $criterion);

        return back()->with('status', 'Criterio agregado.');
    }

    public function update(Request $request, ModeloEvaluacion $instrumento, Dominio $dominio, Criterio $criterio, AuditService $audit): RedirectResponse
    {
        $this->guard($instrumento, $dominio, $criterio);
        $before = $criterio->toArray();
        $criterio->update($this->data($request, $dominio, $criterio));
        $audit->recordModel('CRITERIO_ACTUALIZADO', $criterio, $before);

        return back()->with('status', 'Criterio actualizado.');
    }

    public function destroy(ModeloEvaluacion $instrumento, Dominio $dominio, Criterio $criterio, AuditService $audit): RedirectResponse
    {
        $this->guard($instrumento, $dominio, $criterio);
        $before = $criterio->toArray();
        DB::transaction(function () use ($criterio): void {
            $criterio->descriptores()->delete();
            $criterio->delete();
        });
        $audit->record('CRITERIO_ELIMINADO', 'criterios', $criterio->id, $before);

        return back()->with('status', 'Criterio eliminado.');
    }

    private function guard(ModeloEvaluacion $model, Dominio $domain, ?Criterio $criterion = null): void
    {
        $this->authorize('update', $model);
        abort_unless($domain->modelo_evaluacion_id === $model->id && (! $criterion || $criterion->dominio_id === $domain->id), 404);
    }

    private function data(Request $request, Dominio $domain, ?Criterio $criterion = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:20', Rule::unique('criterios')->where('dominio_id', $domain->id)->ignore($criterion)],
            'nombre' => ['required', 'string', 'max:255'],
            'orden' => ['required', 'integer', 'min:1', Rule::unique('criterios')->where('dominio_id', $domain->id)->ignore($criterion)], 'activo' => ['required', 'boolean'],
        ]);
    }
}
