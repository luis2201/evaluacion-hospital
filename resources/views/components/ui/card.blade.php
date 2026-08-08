@props(['padding' => true])

<section {{ $attributes->class(['app-card', 'p-5 md:p-6' => $padding]) }}>
    {{ $slot }}
</section>
