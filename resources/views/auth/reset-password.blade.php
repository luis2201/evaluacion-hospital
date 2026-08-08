<x-layouts.auth title="Restablecer contraseña">
    <x-ui.card>
        <h1 class="text-2xl font-bold text-navy-900">Nueva contraseña</h1>
        <p class="mt-2 text-sm/6 text-slate-500">Debe tener al menos 12 caracteres, mayúsculas, minúsculas, números y símbolos.</p>
        <form method="POST" action="{{ route('password.store') }}" class="mt-7 space-y-5">@csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <x-ui.form-field label="Correo electrónico" name="email" type="email" :value="$request->email" required autocomplete="email" />
            <x-ui.form-field label="Nueva contraseña" name="password" type="password" required autocomplete="new-password" />
            <x-ui.form-field label="Confirmar contraseña" name="password_confirmation" type="password" required autocomplete="new-password" />
            <button type="submit" class="app-button-primary w-full">Restablecer contraseña</button>
        </form>
    </x-ui.card>
</x-layouts.auth>
