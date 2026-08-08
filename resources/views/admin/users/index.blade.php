<x-layouts.app title="Usuarios">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><x-ui.badge variant="info">Administración</x-ui.badge><h1 class="mt-3 text-2xl font-bold text-navy-900">Usuarios</h1><p class="mt-2 text-sm text-slate-500">Gestiona cuentas, roles y acceso al sistema.</p></div>
        <a href="{{ route('admin.users.create') }}" class="app-button-primary">Crear usuario</a>
    </div>
    <x-ui.card class="mt-6" :padding="false">
        <form method="GET" class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row">
            <input name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o correo" class="min-h-11 min-w-0 flex-1 rounded-xl border border-slate-300 px-3.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-3 focus:ring-brand-100">
            <button class="app-button-secondary">Buscar</button>
        </form>
        <div class="overflow-x-auto"><table class="w-full min-w-[48rem] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">Usuario</th><th class="px-5 py-3">Roles</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Último acceso</th><th class="px-5 py-3 text-right">Acciones</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($users as $user)<tr class="hover:bg-slate-50/70"><td class="px-5 py-4"><p class="font-semibold text-slate-800">{{ $user->name }}</p><p class="text-xs text-slate-500">{{ $user->email }}</p></td><td class="px-5 py-4"><div class="flex flex-wrap gap-1">@foreach($user->roles as $role)<x-ui.badge>{{ $role->nombre }}</x-ui.badge>@endforeach</div></td><td class="px-5 py-4"><x-ui.badge :variant="$user->activo ? 'success' : 'warning'">{{ $user->activo ? 'Activo' : 'Inactivo' }}</x-ui.badge></td><td class="px-5 py-4 text-slate-500">{{ $user->ultimo_acceso_at?->diffForHumans() ?? 'Sin acceso' }}</td><td class="px-5 py-4 text-right"><a href="{{ route('admin.users.edit', $user) }}" class="font-semibold text-brand-700 hover:text-navy-900">Editar</a></td></tr>
            @empty<tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">No se encontraron usuarios.</td></tr>@endforelse
            </tbody></table></div>
        @if($users->hasPages())<div class="border-t border-slate-100 p-4">{{ $users->links() }}</div>@endif
    </x-ui.card>
</x-layouts.app>
