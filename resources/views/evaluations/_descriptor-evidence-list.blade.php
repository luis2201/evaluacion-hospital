@forelse($selectedDescriptors->groupBy(fn($item) => $item->descriptor->criterio_id) as $criterionDescriptors)
    @php($criterion = $criterionDescriptors->first()->descriptor->criterio)
    <section class="mb-5 overflow-hidden rounded-2xl border border-slate-200 last:mb-0">
        <div class="bg-slate-50 px-4 py-3">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-700">{{ $criterion->codigo }}</p>
            <h3 class="mt-1 font-semibold text-slate-800">{{ $criterion->nombre }}</h3>
        </div>
        <div class="divide-y divide-slate-200">
            @foreach($criterionDescriptors as $descriptorItem)
                @php($canModifyEvidence = $allowUpload && ($evaluacion->estado === \App\Enums\EstadoEvaluacion::CargaEvidencias || ($evaluacion->estado === \App\Enums\EstadoEvaluacion::EnEvaluacion && $descriptorItem->estado === \App\Enums\EstadoEvaluacionDescriptor::Observado && $descriptorItem->observaciones->contains('estado', \App\Enums\EstadoObservacion::Abierta))))
                <details class="group bg-white">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-4 hover:bg-slate-50">
                        <div class="min-w-0"><p class="text-sm font-semibold text-slate-800">{{ $descriptorItem->descriptor->codigo }}</p><p class="mt-1 text-sm/6 text-slate-600">{{ $descriptorItem->descriptor->descripcion }}</p></div>
                        <div class="flex shrink-0 items-center gap-3"><x-ui.badge :variant="$descriptorItem->archivos->isNotEmpty() ? 'success' : 'neutral'">{{ $descriptorItem->archivos->count() }} archivo(s)</x-ui.badge><span class="text-slate-400 transition group-open:rotate-180">⌄</span></div>
                    </summary>
                    <div class="border-t border-slate-100 bg-slate-50/60 p-4">
                        <div class="space-y-3">
                            @forelse($descriptorItem->archivos as $file)
                                <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-800">{{ $file->nombre_original }}</p><p class="mt-1 text-xs text-slate-500">{{ strtoupper($file->extension) }} · {{ number_format($file->tamano_bytes / 1024, 1) }} KB · {{ $file->created_at->format('d/m/Y H:i') }}</p>@if($file->descripcion)<p class="mt-2 text-xs text-slate-600">{{ $file->descripcion }}</p>@endif</div>
                                    <div class="flex shrink-0 flex-wrap gap-2">
                                        @if(in_array($file->mime_type, ['application/pdf', 'image/jpeg', 'image/png'], true))<a target="_blank" rel="noopener" href="{{ route('evaluations.descriptors.files.preview', [$evaluacion, $descriptorItem, $file]) }}" class="app-button-secondary">Visualizar</a>@endif
                                        <a href="{{ route('evaluations.descriptors.files.download', [$evaluacion, $descriptorItem, $file]) }}" class="app-button-secondary">Descargar</a>
                                        @if($canModifyEvidence)
                                            <form method="POST" action="{{ route('evaluations.descriptors.files.destroy', [$evaluacion, $descriptorItem, $file]) }}" onsubmit="return confirm('¿Retirar este archivo del descriptor?')">@csrf @method('DELETE')<button class="app-button-secondary text-red-600">Retirar</button></form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="rounded-xl bg-white p-4 text-sm text-slate-500">No hay archivos cargados para este descriptor.</p>
                            @endforelse
                        </div>
                        @if($descriptorItem->enlaces->isNotEmpty())
                            <div class="mt-4 border-t border-slate-200 pt-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Enlaces de evidencia</p>
                                <div class="mt-3 space-y-2">
                                    @foreach($descriptorItem->enlaces as $link)
                                        <div class="flex flex-col gap-2 rounded-xl bg-white p-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="min-w-0"><a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="block truncate text-sm font-semibold text-brand-700">{{ $link->url }}</a>@if($link->descripcion)<p class="mt-1 text-xs text-slate-600">{{ $link->descripcion }}</p>@endif</div>
                                            @if($canModifyEvidence)
                                                <form method="POST" action="{{ route('evaluations.descriptors.links.destroy', [$evaluacion, $descriptorItem, $link]) }}" onsubmit="return confirm('¿Retirar este enlace?')">@csrf @method('DELETE')<button class="text-sm font-semibold text-red-600">Retirar</button></form>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @foreach($descriptorItem->observaciones as $observation)
                            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-2"><div><p class="font-semibold text-slate-800">{{ $observation->asunto }}</p><p class="mt-1 text-xs text-slate-500">Observación del evaluador · {{ $observation->created_at->format('d/m/Y H:i') }}</p></div><x-ui.badge :variant="$observation->estado === \App\Enums\EstadoObservacion::Cerrada ? 'neutral' : 'warning'">{{ str($observation->estado->value)->lower()->headline() }}</x-ui.badge></div>
                                <p class="mt-3 whitespace-pre-line text-sm/6 text-slate-700">{{ $observation->detalle }}</p>
                                @if($observation->fecha_limite)<p class="mt-2 text-xs font-semibold text-amber-800">Fecha límite: {{ $observation->fecha_limite->format('d/m/Y') }}</p>@endif
                                @foreach($observation->respuestas as $answer)<div class="mt-3 rounded-lg bg-white p-3"><p class="text-xs font-semibold text-brand-700">Respuesta enviada</p><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $answer->respuesta }}</p></div>@endforeach
                                @if($observation->estado === \App\Enums\EstadoObservacion::Abierta && auth()->id() === $selectedDomain->responsable_id && $evaluacion->estado === \App\Enums\EstadoEvaluacion::EnEvaluacion)
                                    <form method="POST" action="{{ route('evaluations.descriptors.observations.respond', [$evaluacion, $descriptorItem, $observation]) }}" class="mt-4">@csrf<textarea name="respuesta" required rows="3" maxlength="5000" placeholder="Describe la subsanación realizada" class="w-full rounded-xl border border-amber-300 bg-white p-3 text-sm"></textarea><div class="mt-3 flex justify-end"><button class="app-button-primary">Enviar respuesta</button></div></form>
                                @endif
                            </div>
                        @endforeach
                        @if($canModifyEvidence)
                            <form method="POST" action="{{ route('evaluations.descriptors.files.store', [$evaluacion, $descriptorItem]) }}" enctype="multipart/form-data" class="mt-4 rounded-xl border border-dashed border-brand-300 bg-white p-4">
                                @csrf
                                <label for="files_{{ $descriptorItem->id }}" class="block text-sm font-semibold text-slate-700">Agregar evidencias</label>
                                <p class="mt-1 text-xs text-slate-500">Hasta 10 archivos por envío y 10 MB por archivo.</p>
                                <input id="files_{{ $descriptorItem->id }}" name="archivos[]" type="file" multiple required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf" data-file-selection data-file-counter="files_count_{{ $descriptorItem->id }}" class="mt-3 block w-full rounded-xl border border-slate-300 bg-white p-3 text-sm">
                                <p id="files_count_{{ $descriptorItem->id }}" class="mt-2 text-xs font-semibold text-slate-500" aria-live="polite">Ningún archivo seleccionado</p>
                                <textarea name="descripcion" rows="2" maxlength="1000" placeholder="Descripción opcional para este grupo de archivos" class="mt-3 w-full rounded-xl border border-slate-300 bg-white p-3 text-sm"></textarea>
                                <div class="mt-3 flex justify-end"><button class="app-button-primary">Subir archivos</button></div>
                            </form>
                            <form method="POST" action="{{ route('evaluations.descriptors.links.store', [$evaluacion, $descriptorItem]) }}" class="mt-3 rounded-xl border border-slate-200 bg-white p-4">
                                @csrf
                                <label for="url_{{ $descriptorItem->id }}" class="block text-sm font-semibold text-slate-700">Agregar enlace de evidencia</label>
                                <input id="url_{{ $descriptorItem->id }}" name="url" type="url" required maxlength="2048" placeholder="https://..." class="mt-3 w-full rounded-xl border border-slate-300 bg-white p-3 text-sm">
                                <input name="descripcion" type="text" maxlength="500" placeholder="Descripción opcional" class="mt-3 w-full rounded-xl border border-slate-300 bg-white p-3 text-sm">
                                <div class="mt-3 flex justify-end"><button class="app-button-secondary">Guardar enlace</button></div>
                            </form>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
    </section>
@empty
    <p class="rounded-2xl bg-slate-50 p-5 text-sm text-slate-600">Este dominio no contiene descriptores.</p>
@endforelse
