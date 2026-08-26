<x-layouts.app :title="'Resultados · '.$evaluacion->nombre">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <a href="{{ route('evaluations.show', $evaluacion) }}" class="text-sm font-semibold text-brand-700">← Volver a la evaluación</a>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-ui.badge :variant="$evaluacion->estado === \App\Enums\EstadoEvaluacion::Cerrada ? 'success' : 'info'">{{ $evaluacion->estado === \App\Enums\EstadoEvaluacion::Cerrada ? 'Resultado oficial' : 'Resultado provisional' }}</x-ui.badge>
                <span class="text-sm font-semibold text-slate-400">{{ $evaluacion->codigo }}</span>
            </div>
            <h1 class="mt-3 text-2xl font-bold text-navy-900">Resultados de la evaluación</h1>
            <p class="mt-2 text-sm text-slate-500">Resumen ponderado del instrumento {{ $evaluacion->modeloEvaluacion->nombre }}.</p>
        </div>
        <div class="flex flex-wrap gap-2"><a href="{{ route('evaluations.results.pdf', $evaluacion) }}" class="app-button-secondary">Descargar resultados PDF</a>@can('close', $evaluacion)
            <form method="POST" action="{{ route('admin.evaluations.close', $evaluacion) }}" onsubmit="return confirm('¿Confirmar el cierre formal? Las calificaciones y evidencias quedarán protegidas.')">
                @csrf
                <button class="app-button-primary">Cerrar evaluación</button>
            </form>
        @endcan</div>
    </div>

    @if($errors->any())
        <x-ui.alert variant="warning" title="La evaluación aún no puede cerrarse" class="mt-6">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if(!$general)
        <x-ui.alert variant="warning" title="Resultado no disponible" class="mt-6">La evaluación todavía no tiene descriptores instanciados.</x-ui.alert>
    @else
        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.card><p class="text-sm text-slate-500">Puntaje general</p><p class="mt-2 text-3xl font-bold text-navy-900">{{ number_format((float) $general->puntaje_provisional, 2) }}<span class="text-base text-slate-400"> / 100</span></p></x-ui.card>
            <x-ui.card><p class="text-sm text-slate-500">Avance</p><p class="mt-2 text-3xl font-bold text-navy-900">{{ number_format((float) $general->porcentaje_avance, 2) }} %</p><p class="mt-1 text-xs text-slate-500">{{ $general->descriptores_calificados }} de {{ $general->total_descriptores }} descriptores</p></x-ui.card>
            <x-ui.card><p class="text-sm text-slate-500">Categoría</p><p class="mt-2 text-xl font-bold text-navy-900">{{ $general->categoria_final ?? 'Pendiente' }}</p><p class="mt-1 text-xs text-slate-500">Se oficializa al completar el proceso</p></x-ui.card>
            <x-ui.card><p class="text-sm text-slate-500">Autoevaluaciones enviadas</p><p class="mt-2 text-3xl font-bold text-navy-900">{{ $submitted_self_assessments }} / {{ $total_domains }}</p><p class="mt-1 text-xs {{ $missing_self_assessments ? 'font-semibold text-red-700' : 'text-slate-500' }}">Incumplidas: {{ $missing_self_assessments }} · Pendientes: {{ $pending_self_assessments }}</p></x-ui.card>
        </div>

        @if($evaluacion->estado === \App\Enums\EstadoEvaluacion::Cerrada)
            <x-ui.alert variant="success" title="Evaluación cerrada" class="mt-6">Cierre formal realizado el {{ $evaluacion->cerrada_at?->format('d/m/Y H:i') }} por {{ $evaluacion->cerrador?->name }}. El resultado ya no admite modificaciones.</x-ui.alert>
        @elseif($timeline['scheduled'])
            <x-ui.alert variant="info" title="Evaluación programada" class="mt-6">La carga de evidencias iniciará el {{ $evaluacion->fecha_inicio->format('d/m/Y') }}. Los resultados todavía no son definitivos.</x-ui.alert>
        @elseif($timeline['loading_open'])
            <x-ui.alert variant="info" title="Carga de evidencias abierta" class="mt-6">El plazo finaliza el {{ $evaluacion->fecha_limite_carga->format('d/m/Y') }}. Existen {{ $pending_evidence_descriptors }} descriptores pendientes de evidencia.</x-ui.alert>
        @elseif($timeline['closing_overdue'])
            <x-ui.alert variant="warning" title="Cierre previsto vencido" class="mt-6">La fecha prevista fue {{ $evaluacion->fecha_cierre_prevista->format('d/m/Y') }}. Hay {{ $noncompliant_descriptors }} descriptores incumplidos, {{ $missing_self_assessments }} autoevaluaciones incumplidas, {{ $pending_review_descriptors }} pendientes de revisión, {{ $pending_self_assessments }} autoevaluaciones pendientes y {{ $open_observations }} observaciones por resolver.</x-ui.alert>
        @elseif($timeline['review_started'])
            <x-ui.alert variant="info" title="Revisión en curso" class="mt-6">La carga documental finalizó. Hay {{ $noncompliant_descriptors }} descriptores incumplidos, {{ $missing_self_assessments }} autoevaluaciones incumplidas y {{ $pending_review_descriptors }} pendientes de revisión por el evaluador.</x-ui.alert>
        @endif

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <x-ui.card>
                <h2 class="text-lg font-bold text-navy-900">Resultado por dominio</h2>
                <p class="mt-1 text-sm text-slate-500">Cumplimiento y aporte según el peso del instrumento.</p>
                <div class="mt-6 space-y-5">
                    @foreach($domains as $domain)
                        <div>
                            <div class="flex items-end justify-between gap-3"><div><p class="text-sm font-bold text-slate-800">{{ $domain->dominio_codigo }} · {{ $domain->dominio_nombre }}</p><p class="text-xs text-slate-500">Peso {{ number_format((float) $domain->peso, 2) }} % · aporte {{ number_format((float) $domain->aporte_ponderado_provisional, 2) }}</p></div><span class="text-sm font-bold text-brand-700">{{ number_format((float) $domain->porcentaje_cumplimiento_provisional, 2) }} %</span></div>
                            <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-label="Cumplimiento de {{ $domain->dominio_nombre }}" aria-valuenow="{{ $domain->porcentaje_cumplimiento_provisional }}" aria-valuemin="0" aria-valuemax="100"><div class="h-full rounded-full bg-brand-600" style="width: {{ min(100, (float) $domain->porcentaje_cumplimiento_provisional) }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card>
                <h2 class="text-lg font-bold text-navy-900">Resumen ejecutivo</h2>
                <dl class="mt-5 divide-y divide-slate-100 text-sm">
                    <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Estado del cálculo</dt><dd class="font-bold text-slate-800">{{ str($general->estado_calculo)->lower()->headline() }}</dd></div>
                    <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Dominios calculados</dt><dd class="font-bold text-slate-800">{{ $general->dominios_con_resultado }}</dd></div>
                    <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Descriptores calificados</dt><dd class="font-bold text-slate-800">{{ $general->descriptores_calificados }}</dd></div>
                    @if($timeline['loading_expired'])
                        <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Descriptores incumplidos</dt><dd class="font-bold {{ $noncompliant_descriptors ? 'text-red-700' : 'text-emerald-700' }}">{{ $noncompliant_descriptors }}</dd></div>
                        <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Autoevaluaciones incumplidas</dt><dd class="font-bold {{ $missing_self_assessments ? 'text-red-700' : 'text-emerald-700' }}">{{ $missing_self_assessments }}</dd></div>
                        <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Pendientes de revisión</dt><dd class="font-bold {{ $pending_review_descriptors ? 'text-amber-700' : 'text-emerald-700' }}">{{ $pending_review_descriptors }}</dd></div>
                    @else
                        <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Pendientes de evidencia</dt><dd class="font-bold {{ $pending_evidence_descriptors ? 'text-amber-700' : 'text-emerald-700' }}">{{ $pending_evidence_descriptors }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Fecha efectiva de cierre</dt><dd class="font-bold text-slate-800">{{ $evaluacion->fecha_cierre?->format('d/m/Y') ?? 'Pendiente' }}</dd></div>
                </dl>
            </x-ui.card>
        </div>

        <x-ui.card class="mt-6">
            <h2 class="text-lg font-bold text-navy-900">Detalle por criterio</h2>
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm"><thead class="border-b border-slate-200 text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Criterio</th><th class="px-3 py-3 text-right">Puntos</th><th class="px-3 py-3 text-right">Cumplimiento</th><th class="px-3 py-3 text-right">Avance</th><th class="px-3 py-3">Estado</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($criteria as $criterion)<tr><td class="px-3 py-4"><p class="font-bold text-slate-800">{{ $criterion->criterio_codigo }}</p><p class="mt-1 text-xs text-slate-500">{{ $criterion->criterio_nombre }}</p></td><td class="px-3 py-4 text-right font-semibold">{{ $criterion->puntos_obtenidos }} / {{ $criterion->puntos_maximos }}</td><td class="px-3 py-4 text-right">{{ number_format((float) $criterion->porcentaje_cumplimiento_provisional, 2) }} %</td><td class="px-3 py-4 text-right">{{ number_format((float) $criterion->porcentaje_avance, 2) }} %</td><td class="px-3 py-4"><x-ui.badge :variant="$criterion->estado_calculo === 'COMPLETO' ? 'success' : 'neutral'">{{ str($criterion->estado_calculo)->lower()->headline() }}</x-ui.badge></td></tr>@endforeach</tbody></table>
            </div>
        </x-ui.card>
    @endif
</x-layouts.app>
