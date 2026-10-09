<x-app-layout>
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('areas.show', $area) }}" class="inline-flex items-center gap-2 rounded text-sm font-semibold text-marca-azul transition hover:-translate-x-0.5 hover:text-marca-complementario motion-reduce:transition-none">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Volver a {{ $area->nombre }}
            </a>

            @if (session('aviso'))
                <div role="alert" class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <i class="bi bi-info-circle-fill mt-0.5" aria-hidden="true"></i>
                    <span>{{ session('aviso') }}</span>
                </div>
            @endif

            <header class="aparece tarjeta relative mt-4 overflow-hidden">
                <x-portada :imagen="$modulo->imagen_url" :color="$area->color_acento" :icono="$area->icono" class="h-40 sm:h-52" />
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-marca-azul via-marca-azul/60 to-marca-azul/10"></div>

                <div class="absolute inset-x-0 bottom-0 p-5 sm:p-7">
                    <p class="text-xs font-semibold uppercase tracking-wide text-marca-dorado">{{ $area->nombre }}</p>
                    <h1 class="mt-1 break-words text-2xl font-semibold tracking-tight text-white sm:text-3xl">{{ $modulo->titulo }}</h1>
                </div>
            </header>

            @if ($modulo->descripcion)
                <p class="mt-4 text-marca-gris-texto">{{ $modulo->descripcion }}</p>
            @endif

            @if ($lecciones->isEmpty())
                <div class="tarjeta mt-6 p-8 text-center">
                    <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-marca-azul/5 text-3xl text-marca-azul">
                        <i class="bi bi-journal-text" aria-hidden="true"></i>
                    </span>
                    <h3 class="mt-4 text-lg font-semibold text-marca-azul">Este módulo aún no tiene lecciones</h3>
                    <p class="mt-1 text-marca-gris-texto">Vuelve pronto: el contenido se publicará aquí.</p>
                </div>
            @else
                <h2 class="mt-8 text-lg font-semibold text-marca-azul">Lecciones</h2>
                <ol class="mt-3 space-y-3">
                    @foreach ($lecciones as $leccion)
                        @php $completada = $completadas->contains($leccion->id); @endphp
                        <li class="aparece" style="animation-delay: {{ min($loop->index, 8) * 50 }}ms">
                            <a href="{{ route('lecciones.show', $leccion) }}" class="tarjeta tarjeta-elevable flex items-center gap-4 p-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul focus-visible:ring-offset-2 sm:p-5">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl font-bold {{ $completada ? 'bg-marca-dorado text-marca-azul' : 'bg-marca-azul/5 text-marca-azul' }}">
                                    {{ $loop->iteration }}
                                </span>

                                <div class="min-w-0 flex-1">
                                    <h3 class="break-words text-base font-semibold leading-snug text-marca-azul">{{ $leccion->titulo }}</h3>
                                    @if ($leccion->duracion_min)
                                        <p class="mt-1 text-sm text-marca-gris-texto">
                                            <i class="bi bi-clock" aria-hidden="true"></i> {{ $leccion->duracion_min }} min
                                        </p>
                                    @endif
                                </div>

                                @if ($completada)
                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-marca-dorado text-marca-azul" title="Completada">
                                        <i class="bi bi-check-lg text-lg" aria-hidden="true"></i>
                                    </span>
                                    <span class="sr-only">Completada</span>
                                @else
                                    <i class="bi bi-circle text-2xl text-marca-gris-texto" title="Pendiente" aria-hidden="true"></i>
                                    <span class="sr-only">Pendiente</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ol>
            @endif

            @if ($quiz)
                @php
                    $estado = $estadoQuiz['estado'];
                    $intentoResultado = $estado === 'aprobado' ? $estadoQuiz['aprobado'] : $estadoQuiz['ultimo'];
                    $etiquetaBoton = match ($estado) {
                        'desaprobado' => 'Reintentar quiz del módulo',
                        'aprobado' => 'Volver a rendir el quiz',
                        default => 'Rendir quiz del módulo',
                    };
                @endphp
                <section class="tarjeta relative mt-8 overflow-hidden border-t-4 border-t-marca-dorado p-5 sm:p-6" aria-label="Quiz del módulo">
                    <div class="flex items-start gap-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-marca-azul text-2xl text-marca-dorado">
                            <i class="bi bi-patch-question-fill" aria-hidden="true"></i>
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h3 class="font-semibold text-marca-azul">{{ $quiz->titulo }}</h3>

                                @if ($estado === 'aprobado')
                                    <span class="insignia bg-emerald-50 text-emerald-700">
                                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Aprobado
                                    </span>
                                @elseif ($estado === 'desaprobado')
                                    <span class="insignia bg-amber-50 text-amber-700">
                                        <i class="bi bi-x-circle-fill" aria-hidden="true"></i> No aprobado
                                    </span>
                                @else
                                    <span class="insignia bg-gray-100 text-gray-600">
                                        <i class="bi bi-dash-circle" aria-hidden="true"></i> No rendido
                                    </span>
                                @endif
                            </div>

                            <p class="mt-1 text-sm text-marca-gris-texto">
                                {{ trans_choice(':count pregunta|:count preguntas', $quiz->preguntas_count) }} · Nota mínima: {{ $notaMinima }}%
                            </p>

                            <p class="mt-3 text-sm text-gray-700">
                                @if ($estado === 'aprobado')
                                    Aprobaste con <span class="font-semibold">{{ $estadoQuiz['aprobado']->puntaje }}%</span>
                                    ({{ trans_choice(':count intento|:count intentos', $estadoQuiz['intentos']) }}).
                                @elseif ($estado === 'desaprobado')
                                    Último intento: <span class="font-semibold">{{ $estadoQuiz['ultimo']->puntaje }}%</span>, por debajo de la nota mínima.
                                @else
                                    Aún no has rendido este quiz.
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="mt-5">
                        @if ($leccionesCompletas)
                            <a href="{{ route('quiz.show', $modulo) }}" class="btn w-full {{ $estado === 'aprobado' ? 'btn-contorno' : 'btn-azul' }}">
                                <i class="bi {{ $estado === 'no_rendido' ? 'bi-patch-question' : 'bi-arrow-repeat' }}" aria-hidden="true"></i>
                                {{ $etiquetaBoton }}
                            </a>
                        @else
                            <span aria-disabled="true" class="btn w-full cursor-not-allowed bg-gray-100 text-marca-gris-texto">
                                <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                {{ $etiquetaBoton }}
                            </span>
                            <p class="mt-2 text-center text-sm text-marca-gris-texto">Completa todas las lecciones para desbloquear el quiz.</p>
                        @endif

                        @if ($intentoResultado)
                            <a href="{{ route('quiz.resultado', [$modulo, $intentoResultado]) }}" class="mt-3 flex items-center justify-center gap-1 rounded text-sm font-semibold text-marca-azul hover:text-marca-complementario">
                                <i class="bi bi-card-checklist" aria-hidden="true"></i>
                                {{ $estado === 'aprobado' ? 'Ver mi resultado aprobado' : 'Ver mi último resultado' }}
                            </a>
                        @endif
                    </div>
                </section>
            @endif

            @if ($puedeDescargarResumen)
                <a href="{{ route('modulos.resumen', $modulo) }}" class="btn btn-contorno mt-6 w-full">
                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                    Descargar resumen del módulo
                </a>
            @endif
        </div>
    </div>
</x-app-layout>
