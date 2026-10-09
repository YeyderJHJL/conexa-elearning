<x-app-layout>
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded text-sm font-semibold text-marca-azul transition hover:-translate-x-0.5 hover:text-marca-complementario motion-reduce:transition-none">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Volver al inicio
            </a>

            @if (session('aviso'))
                <div role="alert" class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <i class="bi bi-lock-fill mt-0.5" aria-hidden="true"></i>
                    <span>{{ session('aviso') }}</span>
                </div>
            @endif

            @if (session('estado'))
                <div role="status" class="mt-4 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    <i class="bi bi-check-circle-fill mt-0.5" aria-hidden="true"></i>
                    <span>{{ session('estado') }}</span>
                </div>
            @endif

            <header class="aparece tarjeta relative mt-4 overflow-hidden">
                <x-portada :imagen="$area->imagen_url" :color="$area->color_acento" :icono="$area->icono" class="h-48 sm:h-60" />
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-marca-azul via-marca-azul/60 to-marca-azul/10"></div>

                <div class="absolute inset-x-0 bottom-0 p-5 sm:p-7">
                    <h1 class="break-words text-2xl font-semibold tracking-tight text-white sm:text-4xl">{{ $area->nombre }}</h1>
                    @if ($area->descripcion)
                        <p class="mt-2 line-clamp-2 max-w-2xl text-sm text-marca-gris-claro sm:text-base">{{ $area->descripcion }}</p>
                    @endif
                </div>
            </header>

            @unless ($modulos->isEmpty())
                @include('areas._selector-vista')
            @endunless

            @if ($modulos->isEmpty())
                <div class="tarjeta mt-6 p-8 text-center">
                    <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-marca-azul/5 text-3xl text-marca-azul">
                        <i class="bi bi-collection" aria-hidden="true"></i>
                    </span>
                    <h3 class="mt-4 text-lg font-semibold text-marca-azul">Esta área aún no tiene módulos</h3>
                    <p class="mt-1 text-marca-gris-texto">Vuelve pronto: el contenido se publicará aquí.</p>
                </div>
            @elseif ($vista === 'ruta')
                @include('areas._ruta')
            @else
                <ol class="mt-6 space-y-4" style="--acento: {{ $area->color_acento }}">
                    @foreach ($modulos as $modulo)
                        @php
                            $estado = $estadosModulos[$modulo->id] ?? 'bloqueado';
                            $porcentaje = $progresoModulos[$modulo->id] ?? 0;
                            $completo = $estado === 'completado';
                            $bloqueado = $estado === 'bloqueado';
                            $accesible = ! $bloqueado || $esAdmin;
                        @endphp
                        <li class="aparece" style="animation-delay: {{ min($loop->index, 8) * 60 }}ms">
                            <a
                                @if ($accesible) href="{{ route('modulos.show', $modulo) }}" @else aria-disabled="true" @endif
                                class="tarjeta group flex overflow-hidden {{ $accesible ? 'tarjeta-elevable focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul focus-visible:ring-offset-2' : 'cursor-not-allowed' }} {{ $completo ? 'border-marca-dorado/60' : '' }}"
                            >
                                <span class="relative block w-24 shrink-0 sm:w-44">
                                    <x-portada :imagen="$modulo->imagen_url" :color="$area->color_acento" :icono="$area->icono" class="h-full min-h-[8.5rem] {{ $bloqueado ? 'grayscale' : '' }}" />
                                    <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-marca-azul/70 to-marca-azul/10 {{ $bloqueado ? 'bg-marca-azul/40' : '' }}"></span>
                                    <span class="absolute left-3 top-3 grid h-9 w-9 place-items-center rounded-full text-sm font-bold shadow-nodo ring-2 ring-white {{ $completo ? 'bg-marca-dorado text-marca-azul' : ($bloqueado ? 'bg-marca-gris-claro text-marca-profundo' : 'bg-marca-azul text-white') }}">
                                        @if ($completo)
                                            <i class="bi bi-check-lg text-lg" aria-hidden="true"></i>
                                        @elseif ($bloqueado)
                                            <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                        @else
                                            {{ $loop->iteration }}
                                        @endif
                                    </span>
                                </span>

                                <div class="min-w-0 flex-1 p-4 sm:p-5 {{ $bloqueado ? 'opacity-75' : '' }}">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <h3 class="break-words text-base font-semibold leading-snug text-marca-azul sm:text-lg">{{ $modulo->titulo }}</h3>

                                        @if ($completo)
                                            <span class="insignia bg-marca-dorado text-marca-azul">
                                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Completado
                                            </span>
                                        @elseif ($bloqueado)
                                            <span class="insignia bg-gray-100 text-gray-600">
                                                <i class="bi bi-lock-fill" aria-hidden="true"></i> Bloqueado
                                            </span>
                                        @else
                                            <span class="insignia bg-marca-complementario/15 text-marca-profundo">
                                                <i class="bi bi-play-circle-fill" aria-hidden="true"></i> En curso
                                            </span>
                                        @endif
                                    </div>

                                    @if ($modulo->descripcion)
                                        <p class="mt-1 line-clamp-2 text-sm text-marca-gris-texto">{{ $modulo->descripcion }}</p>
                                    @endif

                                    <p class="mt-2 text-sm text-marca-gris-texto">
                                        <i class="bi bi-journal-text" aria-hidden="true"></i>
                                        {{ trans_choice(':count lección|:count lecciones', $modulo->lecciones_activas_count) }}
                                    </p>

                                    <div class="mt-3 flex items-center gap-3">
                                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-marca-azul/10" role="progressbar" aria-label="Avance de {{ $modulo->titulo }}" aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100">
                                            <div class="h-full rounded-full bg-marca-dorado transition-[width] duration-700 motion-reduce:transition-none" style="width: {{ $porcentaje }}%"></div>
                                        </div>
                                        <span class="text-sm font-bold text-marca-azul">{{ $porcentaje }}%</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach

                    {{-- Último paso de la lista: la encuesta, que solo existe cuando el área está completada. --}}
                    @if ($estadoEncuesta)
                        @php $pendiente = $estadoEncuesta === 'pendiente'; @endphp
                        <li class="aparece">
                            <a
                                @if ($pendiente) href="{{ route('areas.encuesta', $area) }}" @else aria-disabled="true" @endif
                                class="tarjeta flex items-center gap-4 p-4 sm:p-5 {{ $pendiente ? 'tarjeta-elevable border-l-4 border-l-marca-dorado focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul focus-visible:ring-offset-2' : 'cursor-default' }}"
                            >
                                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl text-2xl {{ $pendiente ? 'bg-marca-azul text-marca-dorado' : 'bg-marca-dorado text-marca-azul' }}">
                                    <i class="bi {{ $pendiente ? 'bi-chat-heart-fill' : 'bi-check-lg' }}" aria-hidden="true"></i>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="break-words text-base font-semibold leading-snug text-marca-azul sm:text-lg">Encuesta de satisfacción</h3>

                                        @if ($pendiente)
                                            <span class="insignia bg-marca-complementario/15 text-marca-profundo">
                                                <i class="bi bi-play-circle-fill" aria-hidden="true"></i> Pendiente
                                            </span>
                                        @else
                                            <span class="insignia bg-marca-dorado text-marca-azul">
                                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Respondida
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-1 text-sm text-marca-gris-texto">
                                        {{ $pendiente ? 'Cuéntanos cómo te fue en esta área: son 3 preguntas rápidas.' : 'Gracias por contarnos tu experiencia.' }}
                                    </p>
                                </div>
                            </a>
                        </li>
                    @endif
                </ol>
            @endif

            @if ($puedeDescargarResumen)
                <a href="{{ route('areas.resumen', $area) }}" class="btn btn-contorno mt-6 w-full">
                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                    Descargar resumen del área
                </a>
            @endif
        </div>
    </div>
</x-app-layout>
