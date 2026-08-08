<x-layouts.auth title="Recuperar contraseña">
    <x-ui.card>
        <h1 class="text-2xl font-bold text-navy-900">Recuperar contraseña</h1>
        <p class="mt-2 text-sm/6 text-slate-500">Enviaremos las instrucciones al correo asociado con tu cuenta.</p>
        <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">@csrf
            <x-ui.form-field label="Correo electrónico" name="email" type="email" required autocomplete="email" autofocus />
            <button type="submit" class="app-button-primary w-full">Enviar instrucciones</button>
            <a href="{{ route('login') }}" class="app-button-secondary w-full">Volver al inicio de sesión</a>
        </form>
    </x-ui.card>
</x-layouts.auth>
