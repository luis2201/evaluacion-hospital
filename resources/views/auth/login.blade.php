<x-layouts.auth title="Iniciar sesión">
    <x-ui.card>
        <h1 class="text-2xl font-bold text-navy-900">Bienvenido</h1>
        <p class="mt-2 text-sm/6 text-slate-500">Ingresa tus credenciales institucionales para continuar.</p>
        <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5">@csrf
            <x-ui.form-field label="Correo electrónico" name="email" type="email" required autocomplete="username" autofocus />
            <x-ui.form-field label="Contraseña" name="password" type="password" required autocomplete="current-password" />
            <div class="flex items-center justify-between gap-4">
                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1" class="size-4 rounded border-slate-300 text-navy-900 focus:ring-brand-500"> Recordarme</label>
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-brand-700 hover:text-navy-900">¿Olvidaste tu contraseña?</a>
            </div>
            <button type="submit" class="app-button-primary w-full">Iniciar sesión</button>
        </form>
    </x-ui.card>
</x-layouts.auth>
