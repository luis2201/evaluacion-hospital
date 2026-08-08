@props(['variant' => 'info', 'title'])

@php
    $styles = [
        'info' => 'border-brand-200 bg-brand-50 text-brand-900',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-950',
    ];
@endphp

<div role="alert" {{ $attributes->class(['flex items-start gap-3 rounded-2xl border p-4', $styles[$variant] ?? $styles['info']]) }}>
    <x-ui.icon :name="$variant === 'success' ? 'check' : 'info'" class="mt-0.5 size-5 shrink-0" />
    <div class="min-w-0 flex-1">
        <p class="text-sm font-bold">{{ $title }}</p>
        <div class="mt-1 text-sm/6 opacity-85">{{ $slot }}</div>
    </div>
    <button type="button" data-alert-dismiss class="grid size-8 shrink-0 place-items-center rounded-lg hover:bg-white/60" aria-label="Cerrar alerta">
        <x-ui.icon name="close" class="size-4" />
    </button>
</div>
