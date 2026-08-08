<x-layouts.app title="Nueva versión">
    <div class="mb-6"><a href="{{ route('instruments.index') }}" class="text-sm font-semibold text-brand-700">← Volver</a><h1 class="mt-3 text-2xl font-bold text-navy-900">Nueva versión vacía</h1><p class="mt-2 text-sm text-slate-500">El instrumento se creará como borrador editable.</p></div>
    <x-ui.card class="max-w-3xl"><form method="POST" action="{{ route('admin.instruments.store') }}" class="space-y-5">@csrf
        <x-ui.form-field label="Nombre" name="nombre" required />
        <x-ui.form-field label="Versión" name="version" required placeholder="Ej. 2.0" />
        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700" for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" rows="5" class="w-full rounded-xl border border-slate-300 p-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-3 focus:ring-brand-100">{{ old('descripcion') }}</textarea>@error('descripcion')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div class="flex justify-end gap-3"><a href="{{ route('instruments.index') }}" class="app-button-secondary">Cancelar</a><button class="app-button-primary">Crear borrador</button></div>
    </form></x-ui.card>
</x-layouts.app>
