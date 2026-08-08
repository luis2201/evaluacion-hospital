@props(['title'])

<!DOCTYPE html><html lang="es"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#122b88">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head><body>
<main class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(28rem,0.75fr)]">
    <section class="relative hidden overflow-hidden bg-navy-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <div class="absolute -right-40 -top-40 size-[32rem] rounded-full bg-brand-500/20"></div>
        <div class="relative flex items-center gap-4"><img src="{{ asset('images/brand/hospital-simulacion.png') }}" alt="" class="size-16 rounded-2xl bg-white object-contain p-1"><div><p class="text-xl font-bold">Hospital de Simulación</p><p class="text-sm text-brand-200">Sistema de evaluación de calidad</p></div></div>
        <div class="relative max-w-xl"><p class="text-sm font-bold uppercase tracking-[.18em] text-brand-300">MEC-SIM</p><h1 class="mt-4 text-4xl font-bold leading-tight">Gestión institucional segura y orientada a la mejora continua.</h1><p class="mt-5 text-base/7 text-slate-300">Acceso exclusivo para personal autorizado.</p></div>
        <p class="relative text-xs text-slate-400">Hospital de Simulación Clínica ITS­UP</p>
    </section>
    <section class="flex items-center justify-center bg-slate-50 p-4 sm:p-8"><div class="w-full max-w-md">
        <div class="mb-8 text-center lg:hidden"><img src="{{ asset('images/brand/hospital-simulacion.png') }}" alt="Hospital de Simulación" class="mx-auto h-20 w-auto object-contain"></div>
        @if(session('status'))<x-ui.alert variant="success" title="Información" class="mb-5">{{ session('status') }}</x-ui.alert>@endif
        @if(session('error'))<x-ui.alert variant="warning" title="Atención" class="mb-5">{{ session('error') }}</x-ui.alert>@endif
        {{ $slot }}
    </div></section>
</main></body></html>
