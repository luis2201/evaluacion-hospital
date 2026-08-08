<x-layouts.app title="Editar instrumento">
    <div class="mb-6"><a href="{{ route('instruments.show', $instrumento) }}" class="text-sm font-semibold text-brand-700">← Volver al instrumento</a><h1 class="mt-3 text-2xl font-bold text-navy-900">Datos generales</h1></div>
    <x-ui.card class="max-w-3xl"><form method="POST" action="{{ route('admin.instruments.update', $instrumento) }}" class="space-y-5">@csrf @method('PUT')
        <x-ui.form-field label="Nombre" name="nombre" :value="$instrumento->nombre" required />
        <x-ui.form-field label="Versión" name="version" :value="$instrumento->version" required />
        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700" for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" rows="5" class="w-full rounded-xl border border-slate-300 p-3 text-sm">{{ old('descripcion', $instrumento->descripcion) }}</textarea></div>
        <div class="flex justify-end gap-3"><a href="{{ route('instruments.show', $instrumento) }}" class="app-button-secondary">Cancelar</a><button class="app-button-primary">Guardar</button></div>
    </form></x-ui.card>
</x-layouts.app>
