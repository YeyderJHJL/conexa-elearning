@props(['nodo', 'x', 'y'])

{{--
    Nodo de la ruta de un área. $x es el centro horizontal en % del carril y $y el centro vertical en px.
    Estados: completado (dorado con check), actual (azul destacado con "Continuar"),
    disponible (borde azul) y bloqueado (gris claro con candado, sin enlace).
--}}
@php
    $estado = $nodo['estado'];
    $tipo = $nodo['tipo'];
    $esQuiz = $tipo === 'quiz';
    $esEncuesta = $tipo === 'encuesta';

    $icono = match (true) {
        $estado === 'completado' => 'bi-check-lg',
        $estado === 'bloqueado' => 'bi-lock-fill',
        $estado === 'actual' => $esQuiz ? 'bi-star-fill' : ($esEncuesta ? 'bi-chat-heart-fill' : 'bi-play-fill'),
        default => $esQuiz ? 'bi-star' : ($esEncuesta ? 'bi-chat-heart' : 'bi-book'),
    };

    $circulo = match ($estado) {
        'completado' => 'bg-marca-dorado text-marca-azul shadow-md',
        'actual' => 'bg-marca-azul text-white shadow-lg ring-4 ring-marca-dorado/50',
        'disponible' => 'bg-white text-marca-azul border-4 border-marca-azul shadow-sm',
        default => 'bg-marca-gris-claro text-marca-profundo',
    };

    $tamano = match (true) {
        $estado === 'actual' => 'h-20 w-20 text-4xl',
        $esQuiz => 'h-[4.5rem] w-[4.5rem] text-3xl',
        default => 'h-16 w-16 text-2xl',
    };

    $descripcion = "{$nodo['etiqueta']}: {$nodo['titulo']} ({$estado})";
@endphp

<div
    class="absolute flex w-36 -translate-x-1/2 flex-col items-center"
    style="left: {{ $x }}%; top: {{ $y - 40 }}px"
    data-nodo="{{ $tipo }}"
    data-estado="{{ $estado }}"
>
    <div class="flex h-20 w-20 items-center justify-center">
        @if ($nodo['url'])
            <a href="{{ $nodo['url'] }}" aria-label="{{ $descripcion }}" class="relative flex {{ $tamano }} items-center justify-center rounded-full transition hover:scale-105 motion-reduce:transform-none motion-reduce:transition-none focus:outline-none focus-visible:ring-4 focus-visible:ring-marca-azul {{ $circulo }}">
        @else
            <span role="img" aria-label="{{ $descripcion }}" class="relative flex {{ $tamano }} cursor-not-allowed items-center justify-center rounded-full {{ $circulo }}">
        @endif
                <i class="bi {{ $icono }}" aria-hidden="true"></i>

                @if ($esQuiz && $estado === 'completado')
                    <span class="absolute -right-1 -top-1 flex h-6 w-6 items-center justify-center rounded-full bg-marca-azul text-xs text-marca-dorado ring-2 ring-white">
                        <i class="bi bi-star-fill" aria-hidden="true"></i>
                    </span>
                @endif
        @if ($nodo['url'])
            </a>
        @else
            </span>
        @endif
    </div>

    <p class="mt-1 w-full text-center text-xs font-semibold leading-tight {{ $estado === 'bloqueado' ? 'text-marca-gris-texto' : 'text-marca-azul' }}">{{ $nodo['titulo'] }}</p>
    <p class="text-[0.7rem] text-marca-gris-texto">{{ $nodo['etiqueta'] }}</p>

    @if ($estado === 'actual' && $nodo['url'])
        <a href="{{ $nodo['url'] }}" class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-marca-dorado px-4 py-1.5 text-sm font-semibold text-marca-azul shadow-sm hover:brightness-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul focus-visible:ring-offset-2">
            Continuar
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    @endif
</div>
