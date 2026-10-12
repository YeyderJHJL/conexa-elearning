{{--
    Menú hamburguesa del contenido del área: abre un panel con sus módulos (desplegables), lecciones y quiz.
    Funciona igual en modo lista y en modo ruta. Los nodos bloqueados no son enlaces.
--}}
@php
    $iconoEstado = fn (string $estado, string $tipo) => match (true) {
        $estado === 'completado' => 'bi-check-circle-fill text-marca-azul',
        $estado === 'bloqueado' => 'bi-lock-fill text-marca-gris-texto',
        $estado === 'actual' => 'bi-play-circle-fill text-marca-complementario',
        default => $tipo === 'quiz' ? 'bi-star text-marca-azul' : 'bi-circle text-marca-azul',
    };
@endphp

<div
    x-data="{ abierto: false }"
    x-effect="document.body.classList.toggle('overflow-hidden', abierto)"
    @keydown.escape.window="abierto = false"
    data-menu-contenido
>
    <button
        type="button"
        @click="abierto = true"
        :aria-expanded="abierto.toString()"
        aria-controls="panel-contenido"
        aria-label="Abrir el menú de contenido"
        class="me-2 inline-flex h-11 w-11 items-center justify-center rounded-xl text-2xl text-marca-gris-claro transition duration-150 hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white motion-reduce:transition-none"
    >
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>

    <div x-show="abierto" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Contenido de {{ $area->nombre }}">
        <div
            class="absolute inset-0 bg-marca-azul/60"
            @click="abierto = false"
            x-show="abierto"
            x-transition.opacity
        ></div>

        <aside
            id="panel-contenido"
            x-show="abierto"
            x-transition:enter="transition duration-200 ease-out motion-reduce:transition-none"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition duration-150 ease-in motion-reduce:transition-none"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="absolute inset-y-0 left-0 flex w-80 max-w-[88vw] flex-col bg-white shadow-xl"
        >
            <div class="flex items-start justify-between gap-3 border-b border-marca-azul/10 px-4 py-4">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-marca-gris-texto">Contenido</p>
                    <a href="{{ route('areas.show', $area) }}" class="break-words text-base font-semibold leading-snug text-marca-azul hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul">{{ $area->nombre }}</a>
                </div>

                <button type="button" @click="abierto = false" aria-label="Cerrar el menú" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xl text-marca-azul transition hover:bg-marca-azul/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul motion-reduce:transition-none">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <nav class="min-h-0 flex-1 overflow-y-auto px-2 py-3" aria-label="Módulos y lecciones">
                <ol class="space-y-1">
                    @foreach ($ruta['modulos'] as $tramo)
                        @continue($tramo['nodos']->isEmpty())

                        @php
                            $modulo = $tramo['modulo'];
                            $bloqueado = $tramo['estado'] === 'bloqueado';
                            $esActual = $modulo->id === $moduloActualId;
                        @endphp

                        <li x-data="{ desplegado: {{ $esActual ? 'true' : 'false' }} }" data-menu-modulo="{{ $modulo->id }}">
                            <button
                                type="button"
                                @click="desplegado = ! desplegado"
                                :aria-expanded="desplegado.toString()"
                                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-start transition hover:bg-marca-azul/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul motion-reduce:transition-none {{ $esActual ? 'bg-marca-azul/5' : '' }}"
                            >
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-sm font-bold {{ $tramo['estado'] === 'completado' ? 'bg-marca-dorado text-marca-azul' : ($bloqueado ? 'bg-marca-gris-claro text-marca-profundo' : 'bg-marca-azul text-white') }}">
                                    @if ($tramo['estado'] === 'completado')
                                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                                    @elseif ($bloqueado)
                                        <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block break-words text-sm font-semibold leading-snug text-marca-azul">{{ $modulo->titulo }}</span>
                                    @if ($tramo['lecciones'] > 0)
                                        <span class="block text-xs text-marca-gris-texto">{{ $tramo['vistas'] }} de {{ trans_choice(':count lección|:count lecciones', $tramo['lecciones']) }}</span>
                                    @endif
                                </span>

                                <i class="bi bi-chevron-down text-xs text-marca-gris-texto transition-transform duration-150 motion-reduce:transition-none" :class="desplegado ? 'rotate-180' : ''" aria-hidden="true"></i>
                            </button>

                            <ul x-show="desplegado" x-cloak class="ms-5 mt-1 space-y-0.5 border-s border-marca-azul/10 ps-3">
                                @foreach ($tramo['nodos'] as $nodo)
                                    @php
                                        $actualAqui = $nodo['tipo'] === 'leccion'
                                            ? $nodo['id'] === $leccionActualId
                                            : ($enQuiz && $esActual);
                                    @endphp
                                    <li>
                                        @if ($nodo['url'])
                                            <a
                                                href="{{ $nodo['url'] }}"
                                                @if ($actualAqui) aria-current="page" @endif
                                                class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm transition hover:bg-marca-azul/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul motion-reduce:transition-none {{ $actualAqui ? 'bg-marca-azul/10 font-semibold' : '' }} text-marca-azul"
                                            >
                                                <i class="bi {{ $iconoEstado($nodo['estado'], $nodo['tipo']) }}" aria-hidden="true"></i>
                                                <span class="min-w-0 break-words">{{ $nodo['titulo'] }}</span>
                                            </a>
                                        @else
                                            <span class="flex cursor-not-allowed items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-marca-gris-texto" aria-disabled="true">
                                                <i class="bi {{ $iconoEstado($nodo['estado'], $nodo['tipo']) }}" aria-hidden="true"></i>
                                                <span class="min-w-0 break-words">{{ $nodo['titulo'] }}</span>
                                                <span class="sr-only">(bloqueado)</span>
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ol>
            </nav>

            <div class="border-t border-marca-azul/10 px-4 py-3">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-marca-azul hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul">
                    <i class="bi bi-house" aria-hidden="true"></i> Ir al inicio
                </a>
            </div>
        </aside>
    </div>
</div>
