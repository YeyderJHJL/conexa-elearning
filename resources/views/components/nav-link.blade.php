@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-marca-dorado text-sm font-semibold leading-5 text-white focus:outline-none focus-visible:border-white transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-marca-gris-claro hover:text-white hover:border-marca-gris-claro focus:outline-none focus-visible:text-white focus-visible:border-marca-gris-claro transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
