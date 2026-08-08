<?php

namespace App\Http\Controllers\Instrument;

use App\Http\Controllers\Controller;
use App\Models\Criterio;
use App\Models\Descriptor;
use App\Models\ModeloEvaluacion;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DescriptorController extends Controller
{
    public function store(Request $request, ModeloEvaluacion $instrumento, Criterio $criterio, AuditService $audit): RedirectResponse
    {
        $this->guard($instrumento, $criterio);
        $descriptor = $criterio->descriptores()->create($this->data($request, $criterio));
        $audit->recordModel('DESCRIPTOR_CREADO', $descriptor);

        return back()->with('status', 'Descriptor agregado.');
    }

    public function update(Request $request, ModeloEvaluacion $instrumento, Criterio $criterio, Descriptor $descriptor, AuditService $audit): RedirectResponse
    {
        $this->guard($instrumento, $criterio, $descriptor);
        $before = $descriptor->toArray();
        $descriptor->update($this->data($request, $criterio, $descriptor));
        $audit->recordModel('DESCRIPTOR_ACTUALIZADO', $descriptor, $before);

        return back()->with('status', 'Descriptor actualizado.');
    }

    public function destroy(ModeloEvaluacion $instrumento, Criterio $criterio, Descriptor $descriptor, AuditService $audit): RedirectResponse
    {
        $this->guard($instrumento, $criterio, $descriptor);
        $before = $descriptor->toArray();
        $descriptor->delete();
        $audit->record('DESCRIPTOR_ELIMINADO', 'descriptores', $descriptor->id, $before);

        return back()->with('status', 'Descriptor eliminado.');
    }

    private function guard(ModeloEvaluacion $model, Criterio $criterion, ?Descriptor $descriptor = null): void
    {
        $this->authorize('update', $model);
        $criterion->loadMissing('dominio');
        abort_unless($criterion->dominio->modelo_evaluacion_id === $model->id && (! $descriptor || $descriptor->criterio_id === $criterion->id), 404);
    }

    private function data(Request $request, Criterio $criterion, ?Descriptor $descriptor = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:20', Rule::unique('descriptores')->where('criterio_id', $criterion->id)->ignore($descriptor)],
            'descripcion' => ['required', 'string', 'max:5000'],
            'orden' => ['required', 'integer', 'min:1', Rule::unique('descriptores')->where('criterio_id', $criterion->id)->ignore($descriptor)], 'activo' => ['required', 'boolean'],
        ]) + ['puntaje_maximo' => 2];
    }
}
