<x-layouts.auth title="Configuración inicial">
    <x-ui.card>
        <div class="flex items-start gap-4">
            <span class="icon-box"><x-ui.icon name="shield" class="size-5" /></span>
            <div>
                <x-ui.badge variant="info">Primer acceso</x-ui.badge>
                <h1 class="mt-3 text-2xl font-bold text-navy-900">Crear administrador inicial</h1>
                <p class="mt-2 text-sm/6 text-slate-500">No existen usuarios registrados. Crea la cuenta que administrará el acceso al sistema.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('setup.store') }}" class="mt-7 space-y-5">
            @csrf
            <x-ui.form-field label="Nombre completo" name="name" required autocomplete="name" autofocus />
            <x-ui.form-field label="Correo electrónico" name="email" type="email" required autocomplete="email" />
            <x-ui.form-field label="Contraseña" name="password" type="password" required autocomplete="new-password" />
            <x-ui.form-field label="Confirmar contraseña" name="password_confirmation" type="password" required autocomplete="new-password" />
            <div class="rounded-xl bg-slate-50 p-4 text-xs/5 text-slate-600">
                Utiliza al menos 12 caracteres e incluye mayúsculas, minúsculas, números y símbolos.
            </div>
            <button type="submit" class="app-button-primary w-full">Crear administrador y finalizar</button>
        </form>
    </x-ui.card>
</x-layouts.auth>
