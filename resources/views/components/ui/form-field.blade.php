@props(['label', 'name', 'type' => 'text', 'value' => null, 'required' => false, 'autocomplete' => null])
<div>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ $label }} @if($required)<span class="text-red-600">*</span>@endif</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type !== 'password' ? old($name, $value) : '' }}" @required($required) @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        {{ $attributes->class(['min-h-11 w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:ring-3 focus:ring-brand-100', 'border-red-400' => $errors->has($name), 'border-slate-300' => !$errors->has($name)]) }}>
    @error($name)<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
