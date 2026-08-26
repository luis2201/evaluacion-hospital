<x-layouts.app title="Configuración">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><x-ui.badge variant="info">Experiencia por rol</x-ui.badge><h1 class="mt-3 text-2xl font-bold text-navy-900">Configuración</h1><p class="mt-2 text-sm text-slate-600">Opciones disponibles según las responsabilidades de tu cuenta.</p></div>
        <a href="{{ route('profile.password.edit') }}" class="app-button-secondary">Cambiar contraseña</a>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <main class="space-y-6">
            <x-ui.card>
                <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-bold text-navy-900">Perfil y permisos</h2><p class="mt-1 text-sm text-slate-500">{{ $user->name }} · {{ $user->email }}</p></div><x-ui.badge :variant="$user->activo ? 'success' : 'warning'">{{ $user->activo ? 'Cuenta activa' : 'Cuenta inactiva' }}</x-ui.badge></div>
                <div class="mt-5 grid gap-4 lg:grid-cols-2">@foreach($profiles as $profile)<section class="rounded-2xl border border-slate-200 p-5"><p class="text-xs font-bold uppercase tracking-wider text-brand-700">{{ $profile['role'] }}</p><h3 class="mt-2 font-bold text-slate-800">{{ $profile['name'] }}</h3><p class="mt-2 text-sm/6 text-slate-500">{{ $profile['description'] }}</p><ul class="mt-4 space-y-2 text-sm text-slate-700">@foreach($profile['capabilities'] as $capability)<li class="flex gap-2"><span class="mt-1 text-emerald-600" aria-hidden="true">✓</span><span>{{ $capability }}</span></li>@endforeach</ul></section>@endforeach</div>
            </x-ui.card>

            @if($user->isAdministrator())
                <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">@csrf @method('PUT')
                    <x-ui.card><h2 class="text-lg font-bold text-navy-900">Identidad institucional</h2><p class="mt-1 text-sm text-slate-500">Información general utilizada por la interfaz y los futuros reportes.</p><div class="mt-5 grid gap-4 md:grid-cols-2"><x-ui.form-field label="Nombre institucional" name="institution_name" :value="$settings['institution_name']" required /><x-ui.form-field label="Nombre corto" name="institution_short_name" :value="$settings['institution_short_name']" required /><x-ui.form-field label="Correo de soporte" name="support_email" type="email" :value="$settings['support_email']" class="md:col-span-2" /></div></x-ui.card>
                    <x-ui.card><h2 class="text-lg font-bold text-navy-900">Gestión documental</h2><p class="mt-1 text-sm text-slate-500">Estos límites se aplican en el servidor; no son únicamente informativos.</p><div class="mt-5 grid gap-4 md:grid-cols-2"><x-ui.form-field label="Archivos por selección" name="max_upload_files" type="number" min="1" max="20" :value="$settings['max_upload_files']" required /><x-ui.form-field label="Tamaño máximo por archivo (MB)" name="max_file_size_mb" type="number" min="1" max="50" :value="$settings['max_file_size_mb']" required /></div><p class="mt-4 text-xs text-slate-500">Formatos seguros permitidos: PDF, imágenes, documentos de oficina, TXT y RTF.</p></x-ui.card>
                    <x-ui.card><h2 class="text-lg font-bold text-navy-900">Seguridad</h2><p class="mt-1 text-sm text-slate-500">Políticas comunes para todas las cuentas.</p><div class="mt-5 grid gap-4 md:grid-cols-2"><x-ui.form-field label="Inactividad de sesión (minutos)" name="session_lifetime_minutes" type="number" min="15" max="480" :value="$settings['session_lifetime_minutes']" required /><x-ui.form-field label="Longitud mínima de contraseña" name="minimum_password_length" type="number" min="12" max="32" :value="$settings['minimum_password_length']" required /><x-ui.form-field label="Intentos de inicio de sesión" name="login_attempts" type="number" min="3" max="10" :value="$settings['login_attempts']" required /><x-ui.form-field label="Bloqueo temporal (segundos)" name="login_lock_seconds" type="number" min="30" max="900" :value="$settings['login_lock_seconds']" required /></div><div class="mt-5 flex justify-end"><button class="app-button-primary">Guardar configuración</button></div></x-ui.card>
                </form>

                <x-ui.card>
                    <div class="flex items-center justify-between gap-3"><div><h2 class="text-lg font-bold text-navy-900">Cronograma de evaluaciones</h2><p class="mt-1 text-sm text-slate-500">Consulta y ajusta las fechas de cada proceso activo.</p></div><a href="{{ route('evaluations.index') }}" class="text-sm font-semibold text-brand-700">Ver todas</a></div>
                    <div class="mt-5 divide-y divide-slate-100">
                        @forelse($evaluations as $evaluation)
                            <details class="py-4" @if($errors->any() && old('evaluation_id') == $evaluation->id) open @endif>
                                <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3"><div><p class="font-semibold text-slate-800">{{ $evaluation->nombre }}</p><p class="mt-1 text-xs text-slate-500">Carga hasta {{ $evaluation->fecha_limite_carga?->format('d/m/Y') }} · Revisión {{ $evaluation->fecha_inicio_evaluacion?->format('d/m/Y') }}</p></div><div class="flex items-center gap-2"><x-ui.badge>{{ str($evaluation->estado->value)->lower()->headline() }}</x-ui.badge><span class="text-sm font-semibold text-brand-700">{{ auth()->user()->can('manageSchedule', $evaluation) ? 'Editar fechas' : 'Consultar' }}</span></div></summary>
                                @can('manageSchedule', $evaluation)
                                    <form method="POST" action="{{ route('admin.evaluations.schedule.update', $evaluation) }}" class="mt-5 rounded-2xl bg-slate-50 p-4">@csrf @method('PUT')<input type="hidden" name="evaluation_id" value="{{ $evaluation->id }}"><div class="grid gap-4 md:grid-cols-2"><x-ui.form-field label="Inicio de carga" name="fecha_inicio" type="date" :value="$evaluation->fecha_inicio?->format('Y-m-d')" required /><x-ui.form-field label="Límite de carga" name="fecha_limite_carga" type="date" :value="$evaluation->fecha_limite_carga?->format('Y-m-d')" required /><x-ui.form-field label="Inicio de revisión" name="fecha_inicio_evaluacion" type="date" :value="$evaluation->fecha_inicio_evaluacion?->format('Y-m-d')" required /><x-ui.form-field label="Cierre previsto" name="fecha_cierre_prevista" type="date" :value="$evaluation->fecha_cierre_prevista?->format('Y-m-d')" required /></div><div class="mt-4 flex flex-wrap items-center justify-between gap-3"><p class="text-xs text-slate-500">Los cambios no reabren fases ya finalizadas.</p><button class="app-button-primary">Guardar cronograma</button></div></form>
                                @else
                                    <p class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">El cronograma está protegido porque la evaluación fue finalizada.</p>
                                @endcan
                            </details>
                        @empty<p class="py-6 text-sm text-slate-500">No existen evaluaciones configuradas.</p>@endforelse
                    </div>
                </x-ui.card>
            @endif
        </main>

        <aside class="space-y-6">
            <x-ui.card><h2 class="font-bold text-navy-900">Seguridad personal</h2><p class="mt-3 text-sm/6 text-slate-600">La contraseña es personal y no puede ser consultada ni modificada por otro usuario.</p><a href="{{ route('profile.password.edit') }}" class="mt-4 inline-flex text-sm font-semibold text-brand-700">Actualizar contraseña →</a></x-ui.card>
            @if($system)<x-ui.card><h2 class="font-bold text-navy-900">Estado del sistema</h2><dl class="mt-4 space-y-3 text-sm"><div><dt class="text-slate-500">PHP</dt><dd class="font-semibold text-slate-800">{{ $system['php'] }}</dd></div><div><dt class="text-slate-500">Laravel</dt><dd class="font-semibold text-slate-800">{{ $system['laravel'] }}</dd></div><div><dt class="text-slate-500">MySQL</dt><dd class="break-words font-semibold text-slate-800">{{ $system['database'] }}</dd></div><div><dt class="text-slate-500">Almacenamiento privado</dt><dd class="font-semibold {{ $system['storage_writable'] ? 'text-emerald-700' : 'text-red-700' }}">{{ $system['storage_writable'] ? 'Disponible' : 'Sin escritura' }}</dd></div></dl></x-ui.card>@endif
        </aside>
    </div>
</x-layouts.app>
