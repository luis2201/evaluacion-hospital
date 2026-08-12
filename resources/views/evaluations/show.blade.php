<x-layouts.app :title="$evaluacion->nombre">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <a href="{{ route('evaluations.index') }}" class="text-sm font-semibold text-brand-700">← Evaluaciones</a>
            <div class="mt-3 flex items-center gap-2">
                <x-ui.badge :variant="match($evaluacion->estado->value){'CERRADA'=>'success','CANCELADA'=>'warning','BORRADOR'=>'neutral',default=>'info'}">
                    {{ str($evaluacion->estado->value)->lower()->headline() }}
                </x-ui.badge>
                <span class="text-sm font-semibold text-slate-400">{{ $evaluacion->codigo }}</span>
            </div>
            <h1 class="mt-3 text-2xl font-bold text-navy-900">{{ $evaluacion->nombre }}</h1>
            <p class="mt-2 max-w-3xl text-sm/6 text-slate-500">{{ $evaluacion->descripcion }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('viewResults', $evaluacion)
                <a href="{{ route('evaluations.results', $evaluacion) }}" class="app-button-primary">Resultados</a>
            @endcan
            @can('update', $evaluacion)
                <a href="{{ route('admin.evaluations.edit', $evaluacion) }}" class="app-button-secondary">Configurar</a>
            @endcan
            @can('cancel', $evaluacion)
                <form method="POST" action="{{ route('admin.evaluations.cancel', $evaluacion) }}" onsubmit="return confirm('¿Cancelar esta evaluación?')">@csrf<button class="app-button-secondary">Cancelar proceso</button></form>
            @endcan
        </div>
    </div>

    @if($errors->any())
        <x-ui.alert variant="warning" title="No se pudo completar la operación" class="mt-6">{{ $errors->first() }}</x-ui.alert>
    @endif

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.card><p class="text-sm text-slate-500">Instrumento</p><p class="mt-2 font-bold text-navy-900">v{{ $evaluacion->modeloEvaluacion->version }}</p></x-ui.card>
        <x-ui.card><p class="text-sm text-slate-500">Dominios</p><p class="mt-2 text-2xl font-bold text-navy-900">{{ $evaluacion->dominios->count() }}</p></x-ui.card>
        <x-ui.card><p class="text-sm text-slate-500">Descriptores</p><p class="mt-2 text-2xl font-bold text-navy-900">{{ $evaluacion->descriptores_count }}</p></x-ui.card>
        <x-ui.card><p class="text-sm text-slate-500">Escenario</p><p class="mt-2 font-bold text-navy-900">{{ str($evaluacion->tipo_escenario->value)->lower()->headline() }}</p></x-ui.card>
    </div>

    @if(!$reviewOnly && !$consultOnly)
    <nav class="mt-6 grid gap-3 rounded-3xl border border-slate-200 bg-white p-2 shadow-sm sm:grid-cols-2 {{ $canReview ? 'xl:grid-cols-3' : '' }}" aria-label="Secciones de la evaluación">
        <a href="{{ route('evaluations.show', ['evaluacion' => $evaluacion, 'seccion' => 'ingreso']) }}" class="rounded-2xl px-5 py-4 transition {{ $section === 'ingreso' ? 'bg-navy-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50' }}">
            <span class="block font-bold">Ingreso por dominio</span>
            <span class="mt-1 block text-xs {{ $section === 'ingreso' ? 'text-white/70' : 'text-slate-500' }}">Autoevaluación y carga documental del responsable</span>
        </a>
        <a href="{{ route('evaluations.show', ['evaluacion' => $evaluacion, 'seccion' => 'consulta']) }}" class="rounded-2xl px-5 py-4 transition {{ $section === 'consulta' ? 'bg-navy-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50' }}">
            <span class="block font-bold">Consulta general</span>
            <span class="mt-1 block text-xs {{ $section === 'consulta' ? 'text-white/70' : 'text-slate-500' }}">Todos los dominios, descriptores y documentos</span>
        </a>
        @if($canReview)
            <a href="{{ route('evaluations.show', ['evaluacion' => $evaluacion, 'seccion' => 'revision']) }}" class="rounded-2xl px-5 py-4 transition {{ $section === 'revision' ? 'bg-navy-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50' }}">
                <span class="block font-bold">Revisión del evaluador</span>
                <span class="mt-1 block text-xs {{ $section === 'revision' ? 'text-white/70' : 'text-slate-500' }}">Calificaciones, observaciones y seguimiento</span>
            </a>
        @endif
    </nav>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <main class="min-w-0 space-y-6">
            <x-ui.card>
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <x-ui.badge :variant="$section === 'ingreso' ? 'info' : 'neutral'">{{ $section === 'ingreso' ? 'Área de trabajo' : 'Vista institucional' }}</x-ui.badge>
                        <h2 class="mt-3 text-lg font-bold text-navy-900">Selecciona un dominio</h2>
                        <p class="mt-1 text-sm text-slate-500">Solo se cargará la información del dominio elegido.</p>
                    </div>
                    @if($selectedDomain)
                        <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm lg:max-w-sm">
                            <p class="font-bold text-slate-800">{{ $selectedDomain->dominio->codigo }}</p>
                            <p class="mt-1 text-slate-600">{{ $selectedDomain->dominio->nombre }}</p>
                        </div>
                    @endif
                </div>
                <div class="mt-5 flex flex-wrap gap-2">
                    @forelse($navigationDomains as $domain)
                        <a href="{{ route('evaluations.show', ['evaluacion' => $evaluacion, 'seccion' => $section, 'dominio' => $domain->id]) }}" class="rounded-full border px-4 py-2 text-sm font-semibold transition {{ $selectedDomain?->id === $domain->id ? 'border-brand-700 bg-brand-700 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-100' }}">{{ $domain->dominio->codigo }}</a>
                    @empty
                        <p class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-800">No tienes dominios asignados para ingreso. Utiliza Consulta general para revisar el proceso.</p>
                    @endforelse
                </div>
            </x-ui.card>

            @if($selectedDomain && $section === 'ingreso')
                <x-ui.card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><h2 class="text-lg font-bold text-navy-900">Autoevaluación del dominio</h2><p class="mt-1 text-sm text-slate-500">Valoración narrativa previa a la documentación.</p></div>
                        <x-ui.badge>{{ $selectedDomain->autoevaluacion ? str($selectedDomain->autoevaluacion->estado->value)->lower()->headline() : 'Pendiente' }}</x-ui.badge>
                    </div>
                    @if($selectedDomain->autoevaluacion?->estado === \App\Enums\EstadoAutoevaluacion::Enviada)
                        <div class="mt-5 rounded-2xl bg-slate-50 p-5"><p class="whitespace-pre-line text-sm/6 text-slate-700">{{ $selectedDomain->autoevaluacion->contenido }}</p><p class="mt-4 text-xs text-slate-500">Enviada el {{ $selectedDomain->autoevaluacion->enviada_at?->format('d/m/Y H:i') }}</p></div>
                    @elseif($selectedDomain->autoevaluacion?->estado === \App\Enums\EstadoAutoevaluacion::Incumplida)
                        <x-ui.alert variant="warning" title="Autoevaluación incumplida" class="mt-5">No fue enviada dentro del plazo de carga y ya no puede registrarse.</x-ui.alert>
                    @elseif(auth()->id() === $selectedDomain->responsable_id && $evaluacion->estado === \App\Enums\EstadoEvaluacion::CargaEvidencias)
                        <form method="POST" action="{{ route('evaluations.domains.autoevaluation.store', [$evaluacion, $selectedDomain]) }}" class="mt-5 space-y-4">
                            @csrf
                            <div>
                                <label for="contenido_{{ $selectedDomain->id }}" class="mb-2 block text-sm font-semibold text-slate-700">Contenido de la autoevaluación</label>
                                <textarea id="contenido_{{ $selectedDomain->id }}" name="contenido" rows="8" maxlength="10000" data-word-limit="250" data-word-counter="counter-{{ $selectedDomain->id }}" class="w-full rounded-xl border border-slate-300 bg-white p-3 text-sm">{{ old('contenido', $selectedDomain->autoevaluacion->contenido ?? '') }}</textarea>
                                <p class="mt-2 text-xs text-slate-500">Máximo 250 palabras. Palabras actuales: <span id="counter-{{ $selectedDomain->id }}">0</span></p>
                            </div>
                            <div class="flex flex-wrap gap-3"><button name="estado" value="BORRADOR" class="app-button-secondary">Guardar borrador</button><button name="estado" value="ENVIADA" class="app-button-primary" onclick="return confirm('¿Enviar definitivamente? Después no podrá modificarse.')">Enviar definitivamente</button></div>
                        </form>
                    @elseif($selectedDomain->autoevaluacion && auth()->user()->isAdministrator())
                        <div class="mt-5 rounded-2xl bg-slate-50 p-5"><p class="text-xs font-semibold uppercase text-slate-500">Borrador del responsable</p><p class="mt-3 whitespace-pre-line text-sm/6 text-slate-700">{{ $selectedDomain->autoevaluacion->contenido }}</p></div>
                    @else
                        <p class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">{{ $evaluacion->estado !== \App\Enums\EstadoEvaluacion::CargaEvidencias ? 'La evaluación no está en fase de carga.' : 'La autoevaluación solo puede ser registrada por el responsable del dominio.' }}</p>
                    @endif
                </x-ui.card>

                <x-ui.card>
                    <h2 class="text-lg font-bold text-navy-900">Evidencias por descriptor</h2>
                    <p class="mt-2 text-sm text-slate-500">Abre un descriptor para revisar sus documentos o cargar nuevos archivos.</p>
                    <div class="mt-5">@include('evaluations._descriptor-evidence-list', ['allowUpload' => $canManageSelectedDomain])</div>
                </x-ui.card>
            @elseif($selectedDomain && $section === 'revision')
                <x-ui.card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><h2 class="text-lg font-bold text-navy-900">Autoevaluación del dominio</h2><p class="mt-1 text-sm text-slate-500">Contexto narrativo remitido por el responsable.</p></div>
                        <x-ui.badge>{{ $selectedDomain->autoevaluacion ? str($selectedDomain->autoevaluacion->estado->value)->lower()->headline() : 'Pendiente' }}</x-ui.badge>
                    </div>
                    @if($selectedDomain->autoevaluacion?->estado === \App\Enums\EstadoAutoevaluacion::Enviada)
                        <div class="mt-5 rounded-2xl bg-slate-50 p-5"><p class="whitespace-pre-line text-sm/6 text-slate-700">{{ $selectedDomain->autoevaluacion->contenido }}</p><p class="mt-4 text-xs text-slate-500">Enviada el {{ $selectedDomain->autoevaluacion->enviada_at?->format('d/m/Y H:i') }}</p></div>
                    @elseif($selectedDomain->autoevaluacion?->estado === \App\Enums\EstadoAutoevaluacion::Incumplida)
                        <x-ui.alert variant="warning" title="Autoevaluación incumplida" class="mt-5">El responsable no la envió dentro del plazo establecido.</x-ui.alert>
                    @else
                        <p class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">La autoevaluación todavía no ha sido enviada.</p>
                    @endif
                </x-ui.card>
                <x-ui.card>
                    <h2 class="text-lg font-bold text-navy-900">Bandeja de revisión</h2>
                    <p class="mt-2 text-sm text-slate-500">Revisa documentos, formula observaciones o asigna una calificación por descriptor.</p>
                    @if($evaluacion->estado !== \App\Enums\EstadoEvaluacion::EnEvaluacion)
                        <x-ui.alert variant="warning" title="Revisión no habilitada" class="mt-5">El administrador debe iniciar la fase de revisión antes de calificar.</x-ui.alert>
                    @endif
                    <div class="mt-5">@include('evaluations._descriptor-review-list')</div>
                </x-ui.card>
            @elseif($selectedDomain)
                <x-ui.card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><h2 class="text-lg font-bold text-navy-900">Autoevaluación del dominio</h2><p class="mt-1 text-sm text-slate-500">Consulta de la valoración narrativa enviada por el responsable.</p></div>
                        <x-ui.badge>{{ $selectedDomain->autoevaluacion ? str($selectedDomain->autoevaluacion->estado->value)->lower()->headline() : 'Pendiente' }}</x-ui.badge>
                    </div>
                    @if($selectedDomain->autoevaluacion?->estado === \App\Enums\EstadoAutoevaluacion::Enviada)
                        <div class="mt-5 rounded-2xl bg-slate-50 p-5"><p class="whitespace-pre-line text-sm/6 text-slate-700">{{ $selectedDomain->autoevaluacion->contenido }}</p><p class="mt-4 text-xs text-slate-500">Enviada el {{ $selectedDomain->autoevaluacion->enviada_at?->format('d/m/Y H:i') }}</p></div>
                    @elseif($selectedDomain->autoevaluacion?->estado === \App\Enums\EstadoAutoevaluacion::Incumplida)
                        <x-ui.alert variant="warning" title="Autoevaluación incumplida" class="mt-5">No fue enviada durante la fase de carga documental.</x-ui.alert>
                    @else
                        <p class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">La autoevaluación todavía no ha sido enviada.</p>
                    @endif
                </x-ui.card>
                <x-ui.card>
                    <h2 class="text-lg font-bold text-navy-900">Consulta de descriptores y evidencias</h2>
                    <p class="mt-2 text-sm text-slate-500">Información documental consolidada del dominio seleccionado.</p>
                    <div class="mt-5">@include('evaluations._descriptor-evidence-list', ['allowUpload' => false])</div>
                </x-ui.card>
            @endif
        </main>

        <aside class="space-y-6">
            <x-ui.card><h2 class="font-bold text-navy-900">Responsable activo</h2><p class="mt-3 text-sm font-semibold text-slate-800">{{ $selectedDomain?->responsable?->name ?? 'Sin dominio seleccionado' }}</p><p class="mt-1 text-xs text-slate-500">{{ $selectedDomain?->responsable?->email }}</p></x-ui.card>
            <x-ui.card><h2 class="font-bold text-navy-900">Evaluadores</h2><div class="mt-4 space-y-3">@foreach($evaluacion->evaluadores as $evaluator)<div><p class="text-sm font-semibold text-slate-800">{{ $evaluator->name }}</p><p class="text-xs text-slate-500">{{ $evaluator->pivot->es_principal ? 'Principal' : 'Evaluador' }}</p></div>@endforeach</div></x-ui.card>
            <x-ui.card><h2 class="font-bold text-navy-900">Cronograma</h2><dl class="mt-4 space-y-3 text-sm">@foreach([['Inicio', $evaluacion->fecha_inicio], ['Límite de carga', $evaluacion->fecha_limite_carga], ['Inicio de evaluación', $evaluacion->fecha_inicio_evaluacion], ['Cierre previsto', $evaluacion->fecha_cierre_prevista]] as [$label, $date])<div><dt class="text-slate-500">{{ $label }}</dt><dd class="font-semibold text-slate-700">{{ $date?->format('d/m/Y') ?? 'No definida' }}</dd></div>@endforeach</dl></x-ui.card>
        </aside>
    </div>
</x-layouts.app>
