@props(['imagen' => null, 'color', 'icono' => null])

{{--
    Portada de una tarjeta. Sin imagen (o si esta no carga) queda el fondo de marca:
    degradado del color del área con un brillo dorado y el ícono. $color debe ser un hex validado.
--}}
@php $icono = $icono ?: 'bi-book'; @endphp
<div
    {{ $attributes->class(['relative overflow-hidden']) }}
    style="background: radial-gradient(circle at 85% 15%, rgba(215, 167, 67, 0.40), transparent 55%), linear-gradient(135deg, {{ $color }}, color-mix(in srgb, {{ $color }} 62%, #000));"
>
    <i class="bi {{ $icono }} pointer-events-none absolute -bottom-3 -right-2 text-[6rem] leading-none text-white/10" aria-hidden="true"></i>

    <span class="absolute inset-0 flex items-center justify-center">
        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 text-3xl text-white ring-1 ring-white/25">
            <i class="bi {{ $icono }}" aria-hidden="true"></i>
        </span>
    </span>

    @if ($imagen)
        <img src="{{ $imagen }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
    @endif
</div>
