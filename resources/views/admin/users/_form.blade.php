@php($selectedRoles = old('roles', isset($managedUser) ? $managedUser->roles->pluck('id')->all() : []))
<div class="grid gap-5 md:grid-cols-2">
    <x-ui.form-field label="Nombre completo" name="name" :value="$managedUser->name ?? null" required autocomplete="name" />
    <x-ui.form-field label="Correo electrónico" name="email" type="email" :value="$managedUser->email ?? null" required autocomplete="email" />
    <x-ui.form-field :label="isset($managedUser) ? 'Nueva contraseña (opcional)' : 'Contraseña inicial'" name="password" type="password" :required="!isset($managedUser)" autocomplete="new-password" />
    <x-ui.form-field label="Confirmar contraseña" name="password_confirmation" type="password" :required="!isset($managedUser)" autocomplete="new-password" />
</div>
<div class="mt-6"><p class="text-sm font-semibold text-slate-700">Roles <span class="text-red-600">*</span></p><div class="mt-3 grid gap-3 sm:grid-cols-2">
    @foreach($roles as $role)<label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 p-4 hover:border-brand-300 hover:bg-brand-50/50"><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, $selectedRoles)) class="mt-0.5 size-4 rounded border-slate-300 text-navy-900 focus:ring-brand-500"><span><span class="block text-sm font-semibold text-slate-800">{{ $role->nombre }}</span><span class="mt-1 block text-xs/5 text-slate-500">{{ $role->descripcion }}</span></span></label>@endforeach
    @error('roles')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
</div></div>
<div class="mt-6"><p class="text-sm font-semibold text-slate-700">Estado</p><label class="mt-3 flex items-center gap-3"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" @checked(old('activo', $managedUser->activo ?? true)) class="size-4 rounded border-slate-300 text-navy-900 focus:ring-brand-500"><span class="text-sm text-slate-600">Permitir acceso al sistema</span></label></div>
