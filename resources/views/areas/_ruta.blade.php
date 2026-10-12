{{--
    Modo "ruta" del área: un camino en zigzag por módulo (un nodo por lección y un quiz al final).
    Los estados vienen de RutaService, que a su vez usa el progreso real (ProgresoService).
--}}
@php
    $separacion = 170;                 // px entre centros de nodo
    $holgura = 70;                     // px extra al final para la etiqueta y el botón "Continuar" del último nodo
    $patron = [50, 74, 50, 26];        // centro horizontal (%) del zigzag: centro, derecha, centro, izquierda
    $dorado = config('marca.colores.dorado');
    $grisClaro = config('marca.colores.gris_claro');
    $k = 0;                            // índice global para que el zigzag continúe entre tramos
@endphp

<div
    data-ruta
    x-data
    x-init="$nextTick(() => { const ancla = location.hash ? document.querySelector(location.hash) : null; (ancla ?? $el.querySelector('[data-estado=actual]'))?.scrollIntoView({ block: ancla ? 'start' : 'center' }) })"
    class="mt-4"
>
    @php
        $hayContenido = $ruta['modulos']->contains(fn ($tramo) => $tramo['nodos']->isNotEmpty()) || $ruta['encuesta'];
    @endphp

    @unless ($hayContenido)
        <div class="tarjeta mt-8 p-8 text-center">
            <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-marca-azul/5 text-3xl text-marca-azul">
                <i class="bi bi-signpost-split" aria-hidden="true"></i>
            </span>
            <h3 class="mt-4 text-lg font-semibold text-marca-azul">Tu ruta se está preparando</h3>
            <p class="mt-1 text-marca-gris-texto">Cuando se publiquen las lecciones de esta área, aquí verás el camino paso a paso.</p>
        </div>
    @endunless

    @if ($hayContenido)
        <ul class="flex flex-wrap items-center justify-center gap-x-5 gap-y-1 text-xs text-marca-gris-texto" aria-label="Leyenda">
            <li class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-full bg-marca-dorado"></span> Completado</li>
            <li class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-full bg-marca-azul ring-2 ring-marca-dorado"></span> Actual</li>
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
                return ['x' => $patron[($k + $i) % count($patron)], 'y' => $i * $separacion + 48 + 24];
            });
        @endphp

        <section id="modulo-{{ $tramo['modulo']->id }}" class="mt-10 scroll-mt-20" data-tramo="{{ $estadoModulo }}" aria-label="Módulo {{ $tramo['modulo']->titulo }}">
            {{-- Cartel del módulo: su imagen a la vista; el degradado azul cubre solo el lado del texto. --}}
            @php $conImagen = filled($tramo['modulo']->imagen_url); @endphp
            <header class="tarjeta relative overflow-hidden text-white">
                {{-- La portada va dentro de un contenedor absoluto: x-portada ya trae "relative" y, sin él, quedaba fuera del cartel. --}}
                <div class="absolute inset-0">
                    <x-portada :imagen="$tramo['modulo']->imagen_url" :color="$area->color_acento" :icono="$area->icono" class="h-full w-full" />
                </div>
                {{-- Velo uniforme + degradado más denso del lado del texto: se lee igual con cualquier color de imagen. --}}
                <div class="absolute inset-0 {{ $conImagen ? 'bg-marca-azul/35' : '' }}"></div>
                <div class="absolute inset-0 {{ $conImagen ? 'bg-gradient-to-r from-marca-azul/90 via-marca-azul/60 to-marca-azul/10' : 'bg-gradient-to-r from-marca-azul via-marca-azul/90 to-marca-azul/55' }}"></div>

                <div class="relative flex items-center justify-between gap-3 px-5 py-4 sm:px-6 sm:py-5 {{ $conImagen ? 'min-h-[9rem] sm:min-h-[10.5rem]' : '' }} [text-shadow:0_1px_3px_rgb(0_0_0/0.55)]">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-marca-gris-claro">Módulo {{ $loop->iteration }}</p>
                        <h3 class="break-words text-lg font-semibold leading-snug sm:text-xl">{{ $tramo['modulo']->titulo }}</h3>

                        @if ($tramo['lecciones'] > 0)
                            <p class="mt-1 text-xs text-marca-gris-claro">{{ $tramo['vistas'] }} de {{ trans_choice(':count lección|:count lecciones', $tramo['lecciones']) }}</p>
                        @endif
                    </div>

                    @if ($estadoModulo === 'completado')
                        <span class="insignia shrink-0 bg-marca-dorado text-marca-azul">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Completado
                        </span>
                    @elseif ($estadoModulo === 'bloqueado')
                        <span class="insignia shrink-0 bg-white/10 text-marca-gris-claro">
                            <i class="bi bi-lock-fill" aria-hidden="true"></i> Bloqueado
                        </span>
                    @else
                        <span class="insignia shrink-0 bg-white/15 text-white">
                            <i class="bi bi-play-circle-fill" aria-hidden="true"></i> En curso
                        </span>
                    @endif
                </div>
            </header>

            <div class="relative mx-auto mt-4 w-full max-w-sm sm:max-w-md" style="height: {{ $alto }}px">
                <svg class="absolute inset-0 h-full w-full" viewBox="0 0 100 {{ $alto }}" preserveAspectRatio="none" fill="none" aria-hidden="true">
                    @foreach ($puntos as $i => $punto)
                        @if (! $loop->last)
                            @php
                                $siguiente = $puntos[$i + 1];
                                $recorrido = in_array($nodos[$i + 1]['estado'], ['completado', 'actual'], true);
                                $curva = $separacion / 2 - 5;
                                $trazo = "M {$punto['x']} {$punto['y']} C {$punto['x']} ".($punto['y'] + $curva).", {$siguiente['x']} ".($siguiente['y'] - $curva).", {$siguiente['x']} {$siguiente['y']}";
                            @endphp

                            @if ($recorrido)
                                {{-- Halo suave bajo el tramo ya recorrido --}}
                                <path d="{{ $trazo }}" stroke="{{ $dorado }}" stroke-opacity="0.22" stroke-width="16" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                            @endif

                            <path
                                d="{{ $trazo }}"
                                stroke="{{ $recorrido ? $dorado : $grisClaro }}"
                                stroke-width="{{ $recorrido ? 7 : 5 }}"
                                stroke-linecap="round"
                                @unless ($recorrido) stroke-dasharray="0.1 14" @endunless
                                vector-effect="non-scaling-stroke"
                            />
                        @endif
                    @endforeach
                </svg>

                @foreach ($nodos as $i => $nodo)
                    <x-ruta-nodo :nodo="$nodo" :x="$puntos[$i]['x']" :y="$puntos[$i]['y']" :imagen="$tramo['modulo']->imagen_url" :indice="$i" />
                @endforeach
            </div>
        </section>

        @php $k += $nodos->count(); @endphp
    @endforeach

    @if ($ruta['encuesta'])
        @php $x = $patron[$k % count($patron)]; @endphp

        <section class="mt-10" aria-label="Cierre del área">
            <header class="tarjeta relative overflow-hidden bg-marca-azul px-5 py-4 text-white sm:px-6 sm:py-5">
                <svg class="pointer-events-none absolute -right-8 -top-6 h-32 w-64 max-w-none opacity-20" viewBox="0 0 540 330" aria-hidden="true">
                    <path d="M0 330 C 140 300 380 180 540 0 C 420 150 190 290 0 330 Z" fill="{{ $dorado }}" />
                </svg>
                <div class="relative min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-marca-gris-claro">Cierre del área</p>
                    <h3 class="break-words text-lg font-semibold sm:text-xl">Cuéntanos cómo te fue</h3>
                </div>
            </header>

            <div class="relative mx-auto mt-4 w-full max-w-sm sm:max-w-md" style="height: {{ $separacion + $holgura }}px">
                <x-ruta-nodo :nodo="$ruta['encuesta']" :x="$x" :y="72" />
            </div>
        </section>
    @endif
</div>
