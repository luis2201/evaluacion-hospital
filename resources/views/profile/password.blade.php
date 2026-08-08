<x-layouts.app title="Cambiar contraseña">
    <div class="mb-6"><h1 class="text-2xl font-bold text-navy-900">Cambiar contraseña</h1><p class="mt-2 text-sm text-slate-500">Actualiza periódicamente tu contraseña institucional.</p></div>
    <x-ui.card class="max-w-2xl">
        <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-5">@csrf @method('PUT')
            <x-ui.form-field label="Contraseña actual" name="current_password" type="password" required autocomplete="current-password" />
            <x-ui.form-field label="Nueva contraseña" name="password" type="password" required autocomplete="new-password" />
            <x-ui.form-field label="Confirmar nueva contraseña" name="password_confirmation" type="password" required autocomplete="new-password" />
            <p class="text-xs/5 text-slate-500">Mínimo 12 caracteres, incluyendo mayúsculas, minúsculas, números y símbolos.</p>
            <div class="flex justify-end"><button type="submit" class="app-button-primary">Actualizar contraseña</button></div>
        </form>
    </x-ui.card>
</x-layouts.app>
