@forelse($selectedDescriptors->groupBy(fn($item) => $item->descriptor->criterio_id) as $criterionDescriptors)
    @php($criterion = $criterionDescriptors->first()->descriptor->criterio)
    <section class="mb-5 overflow-hidden rounded-2xl border border-slate-200 last:mb-0">
        <div class="bg-slate-50 px-4 py-3"><p class="text-xs font-bold uppercase tracking-wide text-brand-700">{{ $criterion->codigo }}</p><h3 class="mt-1 font-semibold text-slate-800">{{ $criterion->nombre }}</h3></div>
        <div class="divide-y divide-slate-200">
            @foreach($criterionDescriptors as $descriptorItem)
                @php($pendingObservation = $descriptorItem->observaciones->first(fn($observation) => $observation->estado !== \App\Enums\EstadoObservacion::Cerrada))
                <details class="group bg-white">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-4 hover:bg-slate-50">
                        <div class="min-w-0"><p class="text-sm font-semibold text-slate-800">{{ $descriptorItem->descriptor->codigo }}</p><p class="mt-1 text-sm/6 text-slate-600">{{ $descriptorItem->descriptor->descripcion }}</p></div>
                        <div class="flex shrink-0 items-center gap-2">
                            @if($descriptorItem->calificacion !== null)<x-ui.badge variant="success">{{ $descriptorItem->calificacion->value }} / 2</x-ui.badge>@elseif($pendingObservation)<x-ui.badge variant="warning">Observado</x-ui.badge>@else<x-ui.badge>Pendiente</x-ui.badge>@endif
                            <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
                        </div>
                    </summary>
                    <div class="space-y-5 border-t border-slate-100 bg-slate-50/60 p-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Evidencias disponibles</p>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                @forelse($descriptorItem->archivos as $file)
                                    <div class="rounded-xl border border-slate-200 bg-white p-3"><p class="truncate text-sm font-semibold text-slate-800">{{ $file->nombre_original }}</p><div class="mt-2 flex gap-3 text-xs font-semibold">@if(in_array($file->mime_type, ['application/pdf', 'image/jpeg', 'image/png'], true))<a target="_blank" rel="noopener" href="{{ route('evaluations.descriptors.files.preview', [$evaluacion, $descriptorItem, $file]) }}" class="text-brand-700">Visualizar</a>@endif<a href="{{ route('evaluations.descriptors.files.download', [$evaluacion, $descriptorItem, $file]) }}" class="text-brand-700">Descargar</a></div></div>
                                @empty
                                    <p class="rounded-xl bg-white p-3 text-sm text-slate-500 sm:col-span-2">Sin archivos. Este descriptor no puede calificarse.</p>
                                @endforelse
                            </div>
                        </div>

                        @foreach($descriptorItem->observaciones as $observation)
                            <div class="rounded-xl border {{ $observation->estado === \App\Enums\EstadoObservacion::Cerrada ? 'border-slate-200 bg-white' : 'border-amber-200 bg-amber-50' }} p-4">
                                <div class="flex flex-wrap items-start justify-between gap-2"><div><p class="font-semibold text-slate-800">{{ $observation->asunto }}</p><p class="mt-1 text-xs text-slate-500">{{ $observation->creador->name }} · {{ $observation->created_at->format('d/m/Y H:i') }}</p></div><x-ui.badge :variant="$observation->estado === \App\Enums\EstadoObservacion::Cerrada ? 'neutral' : 'warning'">{{ str($observation->estado->value)->lower()->headline() }}</x-ui.badge></div>
                                <p class="mt-3 whitespace-pre-line text-sm/6 text-slate-700">{{ $observation->detalle }}</p>
                                @foreach($observation->respuestas as $answer)<div class="mt-3 rounded-lg bg-white p-3"><p class="text-xs font-semibold text-brand-700">Respuesta de {{ $answer->autor->name }}</p><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $answer->respuesta }}</p></div>@endforeach
                                @if($observation->estado === \App\Enums\EstadoObservacion::Respondida && $evaluacion->estado === \App\Enums\EstadoEvaluacion::EnEvaluacion)
                                    <form method="POST" action="{{ route('evaluations.descriptors.observations.close', [$evaluacion, $descriptorItem, $observation]) }}" class="mt-3">@csrf<button class="app-button-secondary">Cerrar observación</button></form>
                                @endif
                            </div>
                        @endforeach

                        @if($evaluacion->estado === \App\Enums\EstadoEvaluacion::EnEvaluacion && !$pendingObservation)
                            <div class="grid gap-4 lg:grid-cols-2">
                                <form method="POST" action="{{ route('evaluations.descriptors.review.grade', [$evaluacion, $descriptorItem]) }}" class="rounded-xl border border-emerald-200 bg-white p-4">
                                    @csrf
                                    <p class="text-sm font-bold text-slate-800">Calificar descriptor</p>
                                    <div class="mt-3 grid grid-cols-3 gap-2">@foreach([0 => 'No cumple', 1 => 'Parcial', 2 => 'Cumple'] as $value => $label)<label class="cursor-pointer rounded-lg border border-slate-200 p-2 text-center text-xs font-semibold"><input type="radio" name="calificacion" value="{{ $value }}" required @checked($descriptorItem->calificacion?->value === $value) class="mr-1">{{ $value }} · {{ $label }}</label>@endforeach</div>
                                    <textarea name="observacion_evaluador" rows="3" maxlength="3000" placeholder="Comentario opcional de la calificación" class="mt-3 w-full rounded-xl border border-slate-300 p-3 text-sm">{{ $descriptorItem->observacion_evaluador }}</textarea>
                                    <div class="mt-3 flex justify-end"><button class="app-button-primary" @disabled($descriptorItem->archivos->isEmpty())>Guardar calificación</button></div>
                                </form>
                                @if($descriptorItem->calificacion === null)
                                    <form method="POST" action="{{ route('evaluations.descriptors.observations.store', [$evaluacion, $descriptorItem]) }}" class="rounded-xl border border-amber-200 bg-white p-4">
                                        @csrf
                                        <p class="text-sm font-bold text-slate-800">Solicitar subsanación</p>
                                        <input name="asunto" required maxlength="255" placeholder="Asunto" class="mt-3 w-full rounded-xl border border-slate-300 p-3 text-sm">
                                        <textarea name="detalle" required rows="3" maxlength="5000" placeholder="Describe lo que debe aclararse o corregirse" class="mt-3 w-full rounded-xl border border-slate-300 p-3 text-sm"></textarea>
                                        <input name="fecha_limite" type="date" min="{{ now()->toDateString() }}" class="mt-3 w-full rounded-xl border border-slate-300 p-3 text-sm">
                                        <div class="mt-3 flex justify-end"><button class="app-button-secondary">Enviar observación</button></div>
                                    </form>
                                @endif
                            </div>
                        @endif

                        @if($descriptorItem->historialCalificaciones->isNotEmpty())
                            <details class="rounded-xl bg-white p-4"><summary class="cursor-pointer text-sm font-semibold text-brand-700">Historial de calificaciones ({{ $descriptorItem->historialCalificaciones->count() }})</summary><div class="mt-3 space-y-2">@foreach($descriptorItem->historialCalificaciones as $history)<p class="text-xs text-slate-600">{{ $history->calificada_at->format('d/m/Y H:i') }} · {{ $history->evaluador->name }} · {{ $history->calificacion_anterior?->value ?? 'Sin calificar' }} → {{ $history->calificacion_nueva->value }}</p>@endforeach</div></details>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
    </section>
@empty
    <p class="rounded-2xl bg-slate-50 p-5 text-sm text-slate-600">Este dominio no contiene descriptores.</p>
@endforelse
