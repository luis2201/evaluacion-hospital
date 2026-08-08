@props(['id', 'title', 'description' => null])

<dialog id="{{ $id }}" data-modal class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-3xl bg-transparent p-0 text-slate-700 backdrop:bg-slate-950/55 backdrop:backdrop-blur-sm open:animate-[fade-in_.15s_ease-out]">
    <div class="app-card overflow-hidden shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 md:px-6">
            <div>
                <h2 class="text-lg font-bold text-navy-900">{{ $title }}</h2>
                @if ($description)<p class="mt-1 text-sm text-slate-500">{{ $description }}</p>@endif
            </div>
            <button type="button" data-modal-close class="app-button-ghost size-10 px-0" aria-label="Cerrar modal">
                <x-ui.icon name="close" class="size-5" />
            </button>
        </div>
        <div class="px-5 py-5 md:px-6">{{ $slot }}</div>
        @isset($footer)
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:flex-row sm:justify-end md:px-6">{{ $footer }}</div>
        @endisset
    </div>
</dialog>
