<x-app-layout>
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-marca-azul leading-tight">
            {{ $area->nombre }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-sm font-medium text-marca-azul hover:text-marca-profundo">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Volver al inicio
            </a>

            @if (session('aviso'))
                <div role="alert" class="mt-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <i class="bi bi-lock-fill mt-0.5" aria-hidden="true"></i>
                    <span>{{ session('aviso') }}</span>
                </div>
            @endif

            @if (session('estado'))
                <div role="status" class="mt-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    <i class="bi bi-check-circle-fill mt-0.5" aria-hidden="true"></i>
                    <span>{{ session('estado') }}</span>
                </div>
            @endif

            <x-portada :imagen="$area->imagen_url" :color="$area->color_acento" :icono="$area->icono" class="mt-4 h-28 rounded-2xl sm:h-40" />

            @if ($area->descripcion)
                <p class="mt-4 text-marca-gris">{{ $area->descripcion }}</p>
            @endif

            @unless ($modulos->isEmpty())
                @include('areas._selector-vista')
            @endunless

            @if ($modulos->isEmpty())
                <div class="mt-6 bg-white rounded-2xl shadow-sm p-8 text-center">
                    <i class="bi bi-collection text-4xl text-marca-azul" aria-hidden="true"></i>
                    <h3 class="mt-3 text-lg font-semibold text-marca-azul">Esta área aún no tiene módulos</h3>
                    <p class="mt-1 text-marca-gris">Vuelve pronto: el contenido se publicará aquí.</p>
                </div>
            @elseif ($vista === 'ruta')
                @include('areas._ruta')
            @else
                <ol class="mt-6 space-y-3 sm:space-y-4" style="--acento: {{ $area->color_acento }}">
                    @foreach ($modulos as $modulo)
                        @php
                            $estado = $estadosModulos[$modulo->id] ?? 'bloqueado';
                            $porcentaje = $progresoModulos[$modulo->id] ?? 0;
                            $completo = $estado === 'completado';
                            $bloqueado = $estado === 'bloqueado';
                            $accesible = ! $bloqueado || $esAdmin;
                        @endphp
                        <li>
                            <a
                                @if ($accesible) href="{{ route('modulos.show', $modulo) }}" @else aria-disabled="true" @endif
                                class="flex items-start gap-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 {{ $accesible ? 'transition hover:border-[var(--acento)] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[var(--acento)]' : 'cursor-not-allowed' }} {{ $bloqueado ? 'opacity-70' : '' }}"
                            >
                                @if ($modulo->imagen_url)
                                    <span class="relative block h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-gray-100 sm:h-20 sm:w-20">
                                        <img src="{{ $modulo->imagen_url }}" alt="" loading="lazy" class="h-full w-full object-cover {{ $bloqueado ? 'grayscale' : '' }}" onerror="this.remove()">
                                        <span class="absolute bottom-1 right-1 flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold shadow ring-2 ring-white {{ $completo ? 'bg-marca-dorado text-marca-azul' : ($bloqueado ? 'bg-gray-500 text-white' : 'bg-[var(--acento)] text-white') }}">
                                            @if ($completo)
                                                <i class="bi bi-check-lg" aria-hidden="true"></i>
                                            @elseif ($bloqueado)
                                                <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                            @else
                                                {{ $loop->iteration }}
                                            @endif
                                        </span>
                                    </span>
                                @else
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl font-semibold {{ $completo ? 'bg-marca-dorado/20 text-marca-azul' : ($bloqueado ? 'bg-gray-100 text-marca-gris' : 'bg-[color-mix(in_srgb,var(--acento)_12%,white)] text-[color:var(--acento)]') }}">
                                        @if ($completo)
                                            <i class="bi bi-check-lg text-xl" aria-hidden="true"></i>
                                        @elseif ($bloqueado)
                                            <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                        @else
                                            {{ $loop->iteration }}
                                        @endif
                                    </span>
                                @endif

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="text-base sm:text-lg font-semibold text-marca-azul leading-snug">{{ $modulo->titulo }}</h3>

                                        @if ($completo)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-marca-dorado/15 px-2.5 py-1 text-xs font-medium text-marca-azul">
                                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Completado
                                            </span>
                                        @elseif ($bloqueado)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                                                <i class="bi bi-lock-fill" aria-hidden="true"></i> Bloqueado
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-[color-mix(in_srgb,var(--acento)_12%,white)] px-2.5 py-1 text-xs font-medium text-[color:var(--acento)]">
                                                <i class="bi bi-play-circle" aria-hidden="true"></i> En curso
                                            </span>
                                        @endif
                                    </div>

                                    @if ($modulo->descripcion)
                                        <p class="mt-1 text-sm text-marca-gris">{{ $modulo->descripcion }}</p>
                                    @endif

                                    <p class="mt-3 text-sm text-marca-gris">
                                        <i class="bi bi-journal-text" aria-hidden="true"></i>
                                        {{ trans_choice(':count lección|:count lecciones', $modulo->lecciones_activas_count) }}
                                    </p>

                                    <div class="mt-3 flex items-center gap-3">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100">
                                            <div class="h-full rounded-full bg-marca-dorado" style="width: {{ $porcentaje }}%"></div>
                                        </div>
                                        <span class="text-sm font-semibold text-marca-azul">{{ $porcentaje }}%</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach

                    {{-- Último paso de la lista: la encuesta, que solo existe cuando el área está completada. --}}
                    @if ($estadoEncuesta)
                        @php $pendiente = $estadoEncuesta === 'pendiente'; @endphp
                        <li>
                            <a
                                @if ($pendiente) href="{{ route('areas.encuesta', $area) }}" @else aria-disabled="true" @endif
                                class="flex items-start gap-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 {{ $pendiente ? 'transition hover:border-marca-dorado hover:shadow-md focus:outline-none focus:ring-2 focus:ring-marca-dorado' : 'cursor-default' }}"
                            >
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $pendiente ? 'bg-marca-azul/5 text-marca-azul' : 'bg-marca-dorado/20 text-marca-azul' }}">
                                    <i class="bi {{ $pendiente ? 'bi-chat-heart' : 'bi-check-lg text-xl' }}" aria-hidden="true"></i>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="text-base sm:text-lg font-semibold text-marca-azul leading-snug">Encuesta de satisfacción</h3>

                                        @if ($pendiente)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-marca-azul/5 px-2.5 py-1 text-xs font-medium text-marca-azul">
                                                <i class="bi bi-play-circle" aria-hidden="true"></i> Pendiente
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-marca-dorado/15 px-2.5 py-1 text-xs font-medium text-marca-azul">
                                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Respondida
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-1 text-sm text-marca-gris">
                                        {{ $pendiente ? 'Cuéntanos cómo te fue en esta área: son 3 preguntas rápidas.' : 'Gracias por contarnos tu experiencia.' }}
                                    </p>
                                </div>
                            </a>
                        </li>
                    @endif
                </ol>
            @endif

            @if ($puedeDescargarResumen)
                <a href="{{ route('areas.resumen', $area) }}" class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl border border-marca-azul/20 bg-white px-5 py-3 font-semibold text-marca-azul shadow-sm hover:bg-marca-azul/5 focus:outline-none focus:ring-2 focus:ring-marca-dorado focus:ring-offset-2">
                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                    Descargar resumen del área
                </a>
            @endif
        </div>
    </div>
</x-app-layout>
