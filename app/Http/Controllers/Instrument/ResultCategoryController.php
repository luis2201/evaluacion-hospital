<?php

namespace App\Http\Controllers\Instrument;

use App\Http\Controllers\Controller;
use App\Models\CategoriaResultado;
use App\Models\ModeloEvaluacion;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResultCategoryController extends Controller
{
    public function store(Request $request, ModeloEvaluacion $instrumento, AuditService $audit): RedirectResponse
    {
        $this->authorize('update', $instrumento);
        $category = $instrumento->categoriasResultado()->create($this->data($request, $instrumento));
        $audit->recordModel('CATEGORIA_CREADA', $category);

        return back()->with('status', 'Categoría agregada.');
    }

    public function update(Request $request, ModeloEvaluacion $instrumento, CategoriaResultado $categoria, AuditService $audit): RedirectResponse
    {
        $this->guard($instrumento, $categoria);
        $before = $categoria->toArray();
        $categoria->update($this->data($request, $instrumento, $categoria));
        $audit->recordModel('CATEGORIA_ACTUALIZADA', $categoria, $before);

        return back()->with('status', 'Categoría actualizada.');
    }

    public function destroy(ModeloEvaluacion $instrumento, CategoriaResultado $categoria, AuditService $audit): RedirectResponse
    {
        $this->guard($instrumento, $categoria);
        $before = $categoria->toArray();
        $categoria->delete();
        $audit->record('CATEGORIA_ELIMINADA', 'categorias_resultado', $categoria->id, $before);

        return back()->with('status', 'Categoría eliminada.');
    }

    private function guard(ModeloEvaluacion $model, CategoriaResultado $category): void
    {
        $this->authorize('update', $model);
        abort_unless($category->modelo_evaluacion_id === $model->id, 404);
    }

    private function data(Request $request, ModeloEvaluacion $model, ?CategoriaResultado $category = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:120'], 'porcentaje_desde' => ['required', 'numeric', 'min:0', 'max:100', 'lte:porcentaje_hasta'],
            'porcentaje_hasta' => ['required', 'numeric', 'min:0', 'max:100'], 'interpretacion' => ['nullable', 'string', 'max:2000'],
            'orden' => ['required', 'integer', 'min:1', Rule::unique('categorias_resultado')->where('modelo_evaluacion_id', $model->id)->ignore($category)],
        ]);
    }
}
