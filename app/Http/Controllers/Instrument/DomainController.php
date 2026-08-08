<?php

namespace App\Http\Controllers\Instrument;

use App\Http\Controllers\Controller;
use App\Models\Dominio;
use App\Models\ModeloEvaluacion;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DomainController extends Controller
{
    public function store(Request $request, ModeloEvaluacion $instrumento, AuditService $audit): RedirectResponse
    {
        $this->authorize('update', $instrumento);
        $data = $this->validateData($request, $instrumento);
        $domain = $instrumento->dominios()->create($data);
        $audit->recordModel('DOMINIO_CREADO', $domain);

        return back()->with('status', 'Dominio agregado.');
    }

    public function update(Request $request, ModeloEvaluacion $instrumento, Dominio $dominio, AuditService $audit): RedirectResponse
    {
        $this->authorize('update', $instrumento);
        abort_unless($dominio->modelo_evaluacion_id === $instrumento->id, 404);
        $before = $dominio->toArray();
        $dominio->update($this->validateData($request, $instrumento, $dominio));
        $audit->recordModel('DOMINIO_ACTUALIZADO', $dominio, $before);

        return back()->with('status', 'Dominio actualizado.');
    }

    public function destroy(ModeloEvaluacion $instrumento, Dominio $dominio, AuditService $audit): RedirectResponse
    {
        $this->authorize('update', $instrumento);
        abort_unless($dominio->modelo_evaluacion_id === $instrumento->id, 404);
        $before = $dominio->toArray();
        DB::transaction(function () use ($dominio): void {
            $dominio->criterios->each(fn ($criterion) => $criterion->descriptores()->delete());
            $dominio->criterios()->delete();
            $dominio->delete();
        });
        $audit->record('DOMINIO_ELIMINADO', 'dominios', $dominio->id, $before);

        return back()->with('status', 'Dominio eliminado.');
    }

    private function validateData(Request $request, ModeloEvaluacion $model, ?Dominio $domain = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:20', Rule::unique('dominios')->where('modelo_evaluacion_id', $model->id)->ignore($domain)],
            'nombre' => ['required', 'string', 'max:255'], 'peso' => ['required', 'numeric', 'gt:0', 'max:100'],
            'orden' => ['required', 'integer', 'min:1', Rule::unique('dominios')->where('modelo_evaluacion_id', $model->id)->ignore($domain)],
            'activo' => ['required', 'boolean'],
        ]);
    }
}
