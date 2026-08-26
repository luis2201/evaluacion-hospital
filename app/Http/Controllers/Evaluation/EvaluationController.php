<?php

namespace App\Http\Controllers\Evaluation;

use App\Actions\CreateEvaluation;
use App\Enums\CodigoRol;
use App\Enums\EstadoModeloEvaluacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluation\StoreEvaluationRequest;
use App\Http\Requests\Evaluation\UpdateEvaluationRequest;
use App\Http\Requests\Evaluation\UpdateEvaluationScheduleRequest;
use App\Models\Evaluacion;
use App\Models\ModeloEvaluacion;
use App\Models\User;
use App\Services\AuditService;
use App\Services\EvaluationCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function index(Request $request, EvaluationCalendarService $calendar): View
    {
        $this->authorize('viewAny', Evaluacion::class);
        $calendar->syncAll();
        $evaluations = Evaluacion::query()->visibleTo($request->user())->with('modeloEvaluacion')->withCount(['dominios', 'descriptores'])
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->string('estado')))->latest()->paginate(15)->withQueryString();

        return view('evaluations.index', compact('evaluations'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Evaluacion::class);
        $models = ModeloEvaluacion::query()->where('estado', EstadoModeloEvaluacion::Publicado)->latest('publicado_at')->get();
        $selectedModel = ModeloEvaluacion::query()->where('estado', EstadoModeloEvaluacion::Publicado)->with('dominios')->find($request->integer('modelo_id')) ?? $models->first()?->load('dominios');

        return view('evaluations.create', $this->formData($models, $selectedModel));
    }

    public function store(StoreEvaluationRequest $request, CreateEvaluation $action, AuditService $audit): RedirectResponse
    {
        $evaluation = $action->execute($request->validated(), $request->user());
        $audit->recordModel('EVALUACION_CREADA', $evaluation);

        return redirect()->route('evaluations.show', $evaluation)->with('status', 'Evaluación creada y configurada como borrador.');
    }

    public function show(Request $request, Evaluacion $evaluacion, EvaluationCalendarService $calendar): View
    {
        $this->authorize('view', $evaluacion);
        $calendar->sync($evaluacion);
        $canReview = $request->user()->can('review', $evaluacion);
        $hasAssignedDomains = $evaluacion->dominios()->where('responsable_id', $request->user()->id)->exists();
        $consultOnly = $request->user()->isAdministrator() || $request->user()->hasRole(CodigoRol::AuditorLectura);
        $reviewOnly = $canReview && ! $request->user()->isAdministrator() && ! $hasAssignedDomains;
        $requestedSection = $request->string('seccion')->value();
        $section = match (true) {
            $consultOnly => 'consulta',
            $reviewOnly => 'revision',
            $requestedSection === 'consulta' => 'consulta',
            $requestedSection === 'revision' && $canReview => 'revision',
            default => 'ingreso',
        };
        $evaluacion->load([
            'modeloEvaluacion',
            'dominios.dominio.criterios',
            'dominios.responsable',
            'dominios.autoevaluacion',
            'evaluadores',
        ])->loadCount(['descriptores']);

        $navigationDomains = in_array($section, ['consulta', 'revision'], true)
            ? $evaluacion->dominios
            : $evaluacion->dominios->filter(fn ($domain) => $request->user()->isAdministrator() || $domain->responsable_id === $request->user()->id);

        $selectedDomain = $navigationDomains->firstWhere('id', $request->integer('dominio')) ?? $navigationDomains->first();
        $selectedDescriptors = collect();

        if ($selectedDomain) {
            $selectedDescriptors = $evaluacion->descriptores()
                ->whereHas('descriptor.criterio', fn ($query) => $query->where('dominio_id', $selectedDomain->dominio_id))
                ->with([
                    'descriptor.criterio',
                    'archivos' => fn ($query) => $query->with('cargador')->withCount('descargas')->latest(),
                    'enlaces' => fn ($query) => $query->with('registrador')->latest(),
                    'observaciones' => fn ($query) => $query->with(['creador', 'cerrador', 'respuestas.autor'])->latest(),
                    'historialCalificaciones.evaluador',
                ])
                ->get()
                ->sortBy(fn ($item) => [$item->descriptor->criterio->orden, $item->descriptor->orden]);
        }

        $canManageSelectedDomain = $selectedDomain && ($request->user()->isAdministrator() || $selectedDomain->responsable_id === $request->user()->id);

        return view('evaluations.show', compact('evaluacion', 'section', 'navigationDomains', 'selectedDomain', 'selectedDescriptors', 'canManageSelectedDomain', 'canReview', 'reviewOnly', 'consultOnly'));
    }

    public function edit(Evaluacion $evaluacion): View
    {
        $this->authorize('update', $evaluacion);
        $evaluacion->load('dominios');

        return view('evaluations.edit', $this->formData(collect([$evaluacion->modeloEvaluacion]), $evaluacion->modeloEvaluacion->load('dominios')) + compact('evaluacion'));
    }

    public function update(UpdateEvaluationRequest $request, Evaluacion $evaluacion, AuditService $audit): RedirectResponse
    {
        $data = $request->validated();
        $before = $evaluacion->load('dominios', 'evaluadores')->toArray();
        DB::transaction(function () use ($evaluacion, $data): void {
            $evaluacion->update(collect($data)->except(['responsables', 'evaluadores'])->all());
            foreach ($evaluacion->dominios as $domain) {
                $domain->update(['responsable_id' => $data['responsables'][$domain->dominio_id]]);
            }
            $sync = collect(array_values($data['evaluadores']))->mapWithKeys(fn ($id, $index) => [$id => ['es_principal' => $index === 0, 'asignado_at' => now(), 'created_at' => now(), 'updated_at' => now()]])->all();
            $evaluacion->evaluadores()->sync($sync);
        });
        $audit->recordModel('EVALUACION_CONFIGURADA', $evaluacion->fresh(), $before);

        return redirect()->route('evaluations.show', $evaluacion)->with('status', 'Configuración actualizada.');
    }

    public function updateSchedule(UpdateEvaluationScheduleRequest $request, Evaluacion $evaluacion, AuditService $audit, EvaluationCalendarService $calendar): RedirectResponse
    {
        $before = $evaluacion->getAttributes();
        DB::transaction(fn () => $evaluacion->update($request->validated()));
        $audit->recordModel('EVALUACION_CRONOGRAMA_ACTUALIZADO', $evaluacion->fresh(), $before);
        $calendar->sync($evaluacion);

        return back()->with('status', 'El cronograma de la evaluación fue actualizado.');
    }

    private function formData($models, ?ModeloEvaluacion $selectedModel): array
    {
        $responsibles = User::query()->where('activo', true)->whereHas('roles', fn ($query) => $query->where('codigo', CodigoRol::ResponsableDominio->value))->orderBy('name')->get();
        $evaluators = User::query()->where('activo', true)->whereHas('roles', fn ($query) => $query->where('codigo', CodigoRol::EvaluadorExterno->value))->orderBy('name')->get();

        return compact('models', 'selectedModel', 'responsibles', 'evaluators');
    }
}
