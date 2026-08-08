@props(['title' => 'Panel principal'])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#122b88">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a href="#contenido-principal" class="sr-only z-[100] rounded-lg bg-white px-4 py-2 text-navy-900 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">
        Saltar al contenido
    </a>

    <div class="min-h-screen lg:grid lg:grid-cols-[17.5rem_minmax(0,1fr)]">
        <div data-sidebar-overlay data-sidebar-close hidden class="fixed inset-0 z-40 bg-slate-950/45 backdrop-blur-sm lg:hidden"></div>

        <aside data-sidebar class="fixed inset-y-0 left-0 z-50 flex w-[17.5rem] -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
            <div class="flex h-20 items-center justify-between border-b border-slate-100 px-5">
                <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3" aria-label="Ir al panel principal">
                    <img src="{{ asset('images/brand/hospital-simulacion.png') }}" alt="" class="size-11 rounded-xl object-contain">
                    <span class="min-w-0 leading-tight">
                        <span class="block truncate text-sm font-bold text-navy-900">Hospital de Simulación</span>
                        <span class="block text-xs font-medium text-slate-500">Sistema de evaluación</span>
                    </span>
                </a>
                <button type="button" data-sidebar-close class="app-button-ghost size-11 px-0 lg:hidden" aria-label="Cerrar menú">
                    <x-ui.icon name="close" class="size-5" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-5" aria-label="Navegación principal">
                <p class="mb-2 px-3 text-[0.7rem] font-bold uppercase tracking-[0.16em] text-slate-400">General</p>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                    <x-ui.icon name="dashboard" class="size-5" />
                    Panel principal
                </a>
                <a href="{{ route('instruments.index') }}" class="nav-link {{ request()->routeIs('instruments.*', 'admin.instruments.*') ? 'nav-link-active' : '' }}">
                    <x-ui.icon name="layers" class="size-5" /> Instrumento
                </a>
                <a href="{{ route('evaluations.index') }}" class="nav-link {{ request()->routeIs('evaluations.*', 'admin.evaluations.*') ? 'nav-link-active' : '' }}">
                    <x-ui.icon name="clipboard" class="size-5" /> Evaluaciones
                </a>

                @if(auth()->user()->isAdministrator())
                    <p class="mb-2 mt-7 px-3 text-[0.7rem] font-bold uppercase tracking-[0.16em] text-slate-400">Administración</p>
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'nav-link-active' : '' }}">
                        <x-ui.icon name="users" class="size-5" /> Usuarios
                    </a>
                @endif

                <p class="mb-2 mt-7 px-3 text-[0.7rem] font-bold uppercase tracking-[0.16em] text-slate-400">Próximos módulos</p>
                @foreach ([['settings', 'Configuración']] as [$icon, $label])
                    <span class="nav-link cursor-not-allowed opacity-55" aria-disabled="true">
                        <x-ui.icon :name="$icon" class="size-5" />
                        {{ $label }}
                    </span>
                @endforeach
            </nav>

            <div class="border-t border-slate-100 p-4">
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-xs font-semibold text-slate-500">Entorno inicial</p>
                    <p class="mt-1 text-sm font-bold text-navy-900">Acceso seguro habilitado</p>
                </div>
            </div>
        </aside>

        <div class="min-w-0">
            <header class="sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur md:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" data-sidebar-open class="app-button-secondary size-11 px-0 lg:hidden" aria-label="Abrir menú">
                        <x-ui.icon name="menu" class="size-5" />
                    </button>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-semibold uppercase tracking-wider text-brand-700">MEC-SIM</p>
                        <p class="truncate text-sm font-bold text-slate-800">Gestión de calidad institucional</p>
                    </div>
                </div>

                <details class="relative">
                    <summary class="flex min-h-11 cursor-pointer list-none items-center gap-3 rounded-xl px-2 py-1.5 text-left hover:bg-slate-50" aria-label="Menú de usuario">
                    <span class="grid size-9 place-items-center rounded-full bg-navy-900 text-xs font-bold text-white">{{ str(auth()->user()->name)->explode(' ')->take(2)->map(fn($part) => str($part)->substr(0, 1))->join('') }}</span>
                    <span class="hidden sm:block">
                        <span class="block max-w-44 truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</span>
                        <span class="block text-xs text-slate-500">{{ auth()->user()->roles->pluck('nombre')->join(', ') }}</span>
                    </span>
                    <x-ui.icon name="chevron-down" class="hidden size-4 text-slate-400 sm:block" />
                    </summary>
                    <div class="absolute right-0 top-full z-50 mt-2 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                        <a href="{{ route('profile.password.edit') }}" class="nav-link">Cambiar contraseña</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button type="submit" class="nav-link w-full">Cerrar sesión</button>
                        </form>
                    </div>
                </details>
            </header>

            <main id="contenido-principal" class="mx-auto w-full max-w-[100rem] p-4 md:p-6 lg:p-8">
                @if(session('status'))
                    <x-ui.alert variant="success" title="Operación completada" class="mb-6">{{ session('status') }}</x-ui.alert>
                @endif
                @if(session('error'))
                    <x-ui.alert variant="warning" title="No se pudo completar la operación" class="mb-6">{{ session('error') }}</x-ui.alert>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
