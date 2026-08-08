<?php

namespace App\Http\Controllers\Instrument;

use App\Enums\EstadoModeloEvaluacion;
use App\Http\Controllers\Controller;
use App\Models\ModeloEvaluacion;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstrumentController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ModeloEvaluacion::class);
        $models = ModeloEvaluacion::query()
            ->when(! request()->user()->isAdministrator(), fn ($query) => $query->whereNot('estado', EstadoModeloEvaluacion::Borrador))
            ->withCount(['dominios', 'evaluaciones'])->latest()->paginate(12);

        return view('instruments.index', compact('models'));
    }

    public function show(ModeloEvaluacion $instrumento): View
    {
        $this->authorize('view', $instrumento);
        $instrumento->load('dominios.criterios.descriptores', 'categoriasResultado');

        return view('instruments.show', compact('instrumento'));
    }

    public function create(): View
    {
        $this->authorize('create', ModeloEvaluacion::class);

        return view('instruments.create');
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $this->authorize('create', ModeloEvaluacion::class);
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'version' => ['required', 'string', 'max:30', Rule::unique('modelos_evaluacion')->where('nombre', $request->string('nombre'))],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ]);
        $model = ModeloEvaluacion::query()->create($validated + ['estado' => EstadoModeloEvaluacion::Borrador]);
        $audit->recordModel('INSTRUMENTO_CREADO', $model);

        return redirect()->route('instruments.show', $model)->with('status', 'Versión borrador creada correctamente.');
    }

    public function edit(ModeloEvaluacion $instrumento): View
    {
        $this->authorize('update', $instrumento);

        return view('instruments.edit', compact('instrumento'));
    }

    public function update(Request $request, ModeloEvaluacion $instrumento, AuditService $audit): RedirectResponse
    {
        $this->authorize('update', $instrumento);
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'version' => ['required', 'string', 'max:30', Rule::unique('modelos_evaluacion')->where('nombre', $request->string('nombre'))->ignore($instrumento)],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ]);
        $before = $instrumento->toArray();
        $instrumento->update($validated);
        $audit->recordModel('INSTRUMENTO_ACTUALIZADO', $instrumento, $before);

        return redirect()->route('instruments.show', $instrumento)->with('status', 'Datos generales actualizados.');
    }
}
