@props(['nodo', 'x', 'y'])

{{--
    Nodo de la ruta de un área. $x es el centro horizontal en % del carril y $y el centro vertical en px.
    Estados: completado (dorado con check), actual (azul con anillo dorado, pulso y "Continuar"),
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
        'completado' => 'bg-gradient-to-br from-[#E8C16A] to-marca-dorado text-marca-azul shadow-nodo ring-4 ring-white',
        'actual' => 'bg-marca-azul text-white border-4 border-marca-dorado animate-pulso motion-reduce:animate-none shadow-nodo',
        'disponible' => 'bg-white text-marca-azul border-4 border-marca-azul shadow-nodo',
        default => 'bg-marca-gris-claro text-marca-profundo ring-4 ring-white shadow-sm',
    };

    $tamano = match (true) {
        $estado === 'actual' => 'h-24 w-24 text-5xl',
        $esQuiz => 'h-[5.25rem] w-[5.25rem] text-4xl',
        default => 'h-20 w-20 text-3xl',
    };

    $descripcion = "{$nodo['etiqueta']}: {$nodo['titulo']} ({$estado})";
@endphp

<div
    class="absolute flex w-40 -translate-x-1/2 flex-col items-center"
    style="left: {{ $x }}%; top: {{ $y - 48 }}px"
    data-nodo="{{ $tipo }}"
    data-estado="{{ $estado }}"
>
    <div class="flex h-24 w-24 items-center justify-center">
        @if ($nodo['url'])
            <a href="{{ $nodo['url'] }}" aria-label="{{ $descripcion }}" class="relative flex {{ $tamano }} items-center justify-center rounded-full transition duration-200 hover:scale-110 motion-reduce:transform-none motion-reduce:transition-none focus:outline-none focus-visible:ring-4 focus-visible:ring-marca-complementario focus-visible:ring-offset-2 {{ $circulo }}">
        @else
            <span role="img" aria-label="{{ $descripcion }}" class="relative flex {{ $tamano }} cursor-not-allowed items-center justify-center rounded-full {{ $circulo }}">
        @endif
                <i class="bi {{ $icono }}" aria-hidden="true"></i>

                @if ($esQuiz && $estado === 'completado')
                    <span class="absolute -right-1 -top-1 flex h-7 w-7 items-center justify-center rounded-full bg-marca-azul text-xs text-marca-dorado ring-2 ring-white">
                        <i class="bi bi-star-fill" aria-hidden="true"></i>
                    </span>
                @endif
        @if ($nodo['url'])
            </a>
        @else
            </span>
        @endif
    </div>

    <p class="mt-1.5 w-full text-center text-sm font-semibold leading-tight {{ $estado === 'bloqueado' ? 'text-marca-gris-texto' : 'text-marca-azul' }}">{{ $nodo['titulo'] }}</p>
    <p class="text-xs text-marca-gris-texto">{{ $nodo['etiqueta'] }}</p>

    @if ($estado === 'actual' && $nodo['url'])
        <a href="{{ $nodo['url'] }}" class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-marca-dorado px-4 py-1.5 text-sm font-semibold text-marca-azul shadow-sm transition hover:brightness-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul focus-visible:ring-offset-2 motion-reduce:transition-none">
            Continuar
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    @endif
</div>
