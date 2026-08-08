<x-layouts.app title="Crear usuario">
    <div class="mb-6"><a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-brand-700">← Volver a usuarios</a><h1 class="mt-3 text-2xl font-bold text-navy-900">Crear usuario</h1><p class="mt-2 text-sm text-slate-500">Registra una cuenta y asigna sus permisos iniciales.</p></div>
    <x-ui.card class="max-w-4xl"><form method="POST" action="{{ route('admin.users.store') }}">@csrf @include('admin.users._form')<div class="mt-7 flex justify-end gap-3 border-t border-slate-100 pt-5"><a href="{{ route('admin.users.index') }}" class="app-button-secondary">Cancelar</a><button class="app-button-primary">Crear usuario</button></div></form></x-ui.card>
</x-layouts.app>
