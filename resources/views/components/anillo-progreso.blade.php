@props(['valor' => 0, 'tamano' => 56, 'grosor' => 6, 'color' => null, 'pista' => 'rgb(255 255 255 / 0.18)', 'mostrar' => true, 'etiqueta' => 'Avance'])

{{--
    Anillo de progreso (SVG). El color del texto lo hereda del contenedor (blanco sobre fondo azul).
    Es una barra de progreso accesible: role="progressbar" con nombre y valor.
--}}
@php
    $valor = max(0, min(100, (int) $valor));
    $radio = ($tamano - $grosor) / 2;
    $circunferencia = 2 * M_PI * $radio;
    $desplazamiento = $circunferencia * (1 - $valor / 100);
    $color ??= config('marca.colores.dorado');
    $centro = $tamano / 2;
@endphp

<div
    {{ $attributes->class(['relative inline-grid shrink-0 place-items-center']) }}
    style="width: {{ $tamano }}px; height: {{ $tamano }}px"
    role="progressbar"
    aria-label="{{ $etiqueta }}"
    aria-valuenow="{{ $valor }}"
    aria-valuemin="0"
    aria-valuemax="100"
>
    <svg width="{{ $tamano }}" height="{{ $tamano }}" viewBox="0 0 {{ $tamano }} {{ $tamano }}" class="-rotate-90" aria-hidden="true">
        <circle cx="{{ $centro }}" cy="{{ $centro }}" r="{{ $radio }}" fill="none" stroke="{{ $pista }}" stroke-width="{{ $grosor }}" />

        @if ($valor > 0)
            <circle
                cx="{{ $centro }}"
                cy="{{ $centro }}"
                r="{{ $radio }}"
                fill="none"
                stroke="{{ $color }}"
                stroke-width="{{ $grosor }}"
                stroke-linecap="round"
                stroke-dasharray="{{ round($circunferencia, 2) }}"
                stroke-dashoffset="{{ round($desplazamiento, 2) }}"
                class="transition-[stroke-dashoffset] duration-700 motion-reduce:transition-none"
            />
        @endif
    </svg>

    @if ($mostrar)
        <span class="absolute font-semibold leading-none" style="font-size: {{ max(10, (int) round($tamano * 0.27)) }}px" aria-hidden="true">{{ $valor }}%</span>
    @endif
</div>
