@props(['variant' => 'neutral'])

@php
    $styles = [
        'neutral' => 'bg-slate-100 text-slate-700',
        'info' => 'bg-brand-50 text-brand-800 ring-1 ring-inset ring-brand-200',
        'success' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
        'warning' => 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-200',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', $styles[$variant] ?? $styles['neutral']]) }}>{{ $slot }}</span>
