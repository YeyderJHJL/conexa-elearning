{{--
    Modo "ruta" del área: un camino en zigzag por módulo (un nodo por lección y un quiz al final).
    Los estados vienen de RutaService, que a su vez usa el progreso real (ProgresoService).
--}}
@php
    $separacion = 150;                 // px entre centros de nodo
    $holgura = 50;                     // px extra al final para la etiqueta y el botón "Continuar" del último nodo
    $patron = [50, 71, 50, 29];        // centro horizontal (%) del zigzag: centro, derecha, centro, izquierda
    $dorado = config('marca.colores.dorado');
    $grisClaro = config('marca.colores.gris_claro');
    $k = 0;                            // índice global para que el zigzag continúe entre tramos
@endphp

<div
    data-ruta
    x-data
    x-init="$nextTick(() => $el.querySelector('[data-estado=actual]')?.scrollIntoView({ block: 'center' }))"
    class="mt-4"
>
    @php
        $hayContenido = $ruta['modulos']->contains(fn ($tramo) => $tramo['nodos']->isNotEmpty()) || $ruta['encuesta'];
    @endphp

    @unless ($hayContenido)
        <div class="mt-8 rounded-2xl bg-white p-8 text-center shadow-sm">
            <i class="bi bi-signpost-split text-4xl text-marca-azul" aria-hidden="true"></i>
            <h3 class="mt-3 text-lg font-semibold text-marca-azul">Tu ruta se está preparando</h3>
            <p class="mt-1 text-marca-gris-texto">Cuando se publiquen las lecciones de esta área, aquí verás el camino paso a paso.</p>
        </div>
    @endunless

    @if ($hayContenido)
        <ul class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-xs text-marca-gris-texto" aria-label="Leyenda">
            <li class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-full bg-marca-dorado"></span> Completado</li>
            <li class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-full bg-marca-azul"></span> Actual</li>
            <li class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-full bg-marca-gris-claro"></span> Bloqueado</li>
        </ul>
    @endif

    @foreach ($ruta['modulos'] as $tramo)
        @continue($tramo['nodos']->isEmpty())

        @php
            $estadoModulo = $tramo['estado'];
            $nodos = $tramo['nodos'];
            $alto = $nodos->count() * $separacion + $holgura;
            $puntos = $nodos->map(function ($nodo, $i) use ($patron, $k, $separacion) {
                return ['x' => $patron[($k + $i) % count($patron)], 'y' => $i * $separacion + 40 + 36];
            });
        @endphp

        <section class="mt-8" data-tramo="{{ $estadoModulo }}" aria-label="Módulo {{ $tramo['modulo']->titulo }}">
            <header class="flex items-center justify-between gap-3 rounded-2xl bg-marca-azul px-4 py-3 text-white shadow-sm">
                <div class="min-w-0">
                    <p class="text-xs text-marca-gris-claro">Módulo {{ $loop->iteration }}</p>
                    <h3 class="break-words font-semibold">{{ $tramo['modulo']->titulo }}</h3>
                </div>

                @if ($estadoModulo === 'completado')
                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-marca-dorado px-2.5 py-1 text-xs font-semibold text-marca-azul">
                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Completado
                    </span>
                @elseif ($estadoModulo === 'bloqueado')
                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-white/10 px-2.5 py-1 text-xs font-medium text-marca-gris-claro">
                        <i class="bi bi-lock-fill" aria-hidden="true"></i> Bloqueado
                    </span>
                @else
                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-xs font-medium text-white">
                        <i class="bi bi-play-circle" aria-hidden="true"></i> En curso
                    </span>
                @endif
            </header>

            @if ($tramo['lecciones'] > 0)
                <p class="mt-2 text-center text-xs text-marca-gris-texto">{{ $tramo['vistas'] }} de {{ trans_choice(':count lección|:count lecciones', $tramo['lecciones']) }}</p>
            @endif

            <div class="relative mx-auto mt-4 w-full max-w-xs sm:max-w-sm" style="height: {{ $alto }}px">
                <svg class="absolute inset-0 h-full w-full" viewBox="0 0 100 {{ $alto }}" preserveAspectRatio="none" fill="none" aria-hidden="true">
                    @foreach ($puntos as $i => $punto)
                        @if (! $loop->last)
                            @php
                                $siguiente = $puntos[$i + 1];
                                $recorrido = in_array($nodos[$i + 1]['estado'], ['completado', 'actual'], true);
                                $curva = $separacion / 2 - 5;
                            @endphp
                            <path
                                d="M {{ $punto['x'] }} {{ $punto['y'] }} C {{ $punto['x'] }} {{ $punto['y'] + $curva }}, {{ $siguiente['x'] }} {{ $siguiente['y'] - $curva }}, {{ $siguiente['x'] }} {{ $siguiente['y'] }}"
                                stroke="{{ $recorrido ? $dorado : $grisClaro }}"
                                stroke-width="{{ $recorrido ? 6 : 5 }}"
                                stroke-linecap="round"
                                @unless ($recorrido) stroke-dasharray="0.1 14" @endunless
                                vector-effect="non-scaling-stroke"
                            />
                        @endif
                    @endforeach
                </svg>

                @foreach ($nodos as $i => $nodo)
                    <x-ruta-nodo :nodo="$nodo" :x="$puntos[$i]['x']" :y="$puntos[$i]['y']" />
                @endforeach
            </div>
        </section>

        @php $k += $nodos->count(); @endphp
    @endforeach

    @if ($ruta['encuesta'])
        @php $x = $patron[$k % count($patron)]; @endphp

        <section class="mt-8" aria-label="Cierre del área">
            <header class="flex items-center justify-between gap-3 rounded-2xl bg-marca-azul px-4 py-3 text-white shadow-sm">
                <div class="min-w-0">
                    <p class="text-xs text-marca-gris-claro">Cierre del área</p>
                    <h3 class="break-words font-semibold">Cuéntanos cómo te fue</h3>
                </div>
            </header>

            <div class="relative mx-auto mt-4 w-full max-w-xs sm:max-w-sm" style="height: {{ $separacion + $holgura }}px">
                <x-ruta-nodo :nodo="$ruta['encuesta']" :x="$x" :y="76" />
            </div>
        </section>
    @endif
</div>
