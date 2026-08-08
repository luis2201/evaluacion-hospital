<x-layouts.app title="Instrumentos de evaluación">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><x-ui.badge variant="info">MEC-SIM</x-ui.badge><h1 class="mt-3 text-2xl font-bold text-navy-900">Instrumentos de evaluación</h1><p class="mt-2 text-sm text-slate-500">Consulta las versiones y su estado de uso institucional.</p></div>
        @can('create', App\Models\ModeloEvaluacion::class)<a href="{{ route('admin.instruments.create') }}" class="app-button-primary">Nueva versión vacía</a>@endcan
    </div>
    <div class="mt-6 grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
        @forelse($models as $model)
            <x-ui.card class="flex flex-col">
                <div class="flex items-start justify-between gap-3"><x-ui.badge :variant="match($model->estado->value){'PUBLICADO'=>'success','BORRADOR'=>'warning',default=>'neutral'}">{{ str($model->estado->value)->lower()->headline() }}</x-ui.badge><span class="text-xs font-semibold text-slate-400">v{{ $model->version }}</span></div>
                <h2 class="mt-4 text-lg font-bold text-navy-900">{{ $model->nombre }}</h2><p class="mt-2 line-clamp-3 text-sm/6 text-slate-500">{{ $model->descripcion ?: 'Sin descripción.' }}</p>
                <div class="mt-5 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-3 text-center"><div><p class="text-xl font-bold text-navy-900">{{ $model->dominios_count }}</p><p class="text-xs text-slate-500">Dominios</p></div><div><p class="text-xl font-bold text-navy-900">{{ $model->evaluaciones_count }}</p><p class="text-xs text-slate-500">Evaluaciones</p></div></div>
                <a href="{{ route('instruments.show', $model) }}" class="app-button-secondary mt-5 w-full">Abrir instrumento</a>
            </x-ui.card>
        @empty<x-ui.card class="lg:col-span-2 xl:col-span-3"><p class="py-8 text-center text-slate-500">No existen instrumentos registrados.</p></x-ui.card>@endforelse
    </div>
    @if($models->hasPages())<div class="mt-6">{{ $models->links() }}</div>@endif
</x-layouts.app>
