<x-app-layout>
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-marca-azul leading-tight">
            {{ $modulo->titulo }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ route('areas.show', $area) }}" class="inline-flex items-center gap-2 text-sm font-medium text-marca-azul hover:text-marca-profundo">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Volver a {{ $area->nombre }}
            </a>

            @if (session('aviso'))
                <div role="alert" class="mt-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <i class="bi bi-info-circle-fill mt-0.5" aria-hidden="true"></i>
                    <span>{{ session('aviso') }}</span>
                </div>
            @endif

            @if ($modulo->descripcion)
                <p class="mt-4 text-marca-gris">{{ $modulo->descripcion }}</p>
            @endif

            @if ($lecciones->isEmpty())
                <div class="mt-6 bg-white rounded-2xl shadow-sm p-8 text-center">
                    <i class="bi bi-journal-text text-4xl text-marca-azul" aria-hidden="true"></i>
                    <h3 class="mt-3 text-lg font-semibold text-marca-azul">Este módulo aún no tiene lecciones</h3>
                    <p class="mt-1 text-marca-gris">Vuelve pronto: el contenido se publicará aquí.</p>
                </div>
            @else
                <ol class="mt-6 space-y-3">
                    @foreach ($lecciones as $leccion)
                        @php $completada = $completadas->contains($leccion->id); @endphp
                        <li>
                            <a href="{{ route('lecciones.show', $leccion) }}" class="flex items-center gap-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 transition hover:border-marca-dorado hover:shadow-md focus:outline-none focus:ring-2 focus:ring-marca-dorado">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-marca-azul/5 text-marca-azul font-semibold">
                                    {{ $loop->iteration }}
                                </span>

                                <div class="min-w-0 flex-1">
                                    <h3 class="text-base font-semibold text-marca-azul leading-snug">{{ $leccion->titulo }}</h3>
                                    @if ($leccion->duracion_min)
                                        <p class="mt-1 text-sm text-marca-gris">
                                            <i class="bi bi-clock" aria-hidden="true"></i> {{ $leccion->duracion_min }} min
                                        </p>
                                    @endif
                                </div>

                                @if ($completada)
                                    <i class="bi bi-check-circle-fill text-2xl text-marca-dorado" title="Completada" aria-hidden="true"></i>
                                    <span class="sr-only">Completada</span>
                                @else
                                    <i class="bi bi-circle text-2xl text-gray-300" title="Pendiente" aria-hidden="true"></i>
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
                <section class="mt-6 rounded-2xl bg-white p-5 shadow-sm border border-gray-100" aria-label="Quiz del módulo">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="font-semibold text-marca-azul">{{ $quiz->titulo }}</h3>
                            <p class="mt-1 text-sm text-marca-gris">
                                {{ trans_choice(':count pregunta|:count preguntas', $quiz->preguntas_count) }} · Nota mínima: {{ $notaMinima }}%
                            </p>
                        </div>

                        @if ($estado === 'aprobado')
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Aprobado
                            </span>
                        @elseif ($estado === 'desaprobado')
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                <i class="bi bi-x-circle-fill" aria-hidden="true"></i> No aprobado
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                                <i class="bi bi-dash-circle" aria-hidden="true"></i> No rendido
                            </span>
                        @endif
                    </div>

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

                    <div class="mt-4">
                        @if ($leccionesCompletas)
                            <a href="{{ route('quiz.show', $modulo) }}" class="flex w-full items-center justify-center gap-2 rounded-xl px-5 py-3 font-semibold focus:outline-none focus:ring-2 focus:ring-marca-dorado focus:ring-offset-2 {{ $estado === 'aprobado' ? 'border border-marca-azul/20 text-marca-azul hover:bg-marca-azul/5' : 'bg-marca-azul text-white shadow-sm hover:bg-marca-profundo' }}">
                                <i class="bi {{ $estado === 'no_rendido' ? 'bi-patch-question' : 'bi-arrow-repeat' }}" aria-hidden="true"></i>
                                {{ $etiquetaBoton }}
                            </a>
                        @else
                            <span aria-disabled="true" class="flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-gray-100 px-5 py-3 font-semibold text-marca-gris">
                                <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                {{ $etiquetaBoton }}
                            </span>
                            <p class="mt-2 text-center text-sm text-marca-gris">Completa todas las lecciones para desbloquear el quiz.</p>
                        @endif

                        @if ($intentoResultado)
                            <a href="{{ route('quiz.resultado', [$modulo, $intentoResultado]) }}" class="mt-3 flex items-center justify-center gap-1 text-sm font-medium text-marca-azul hover:text-marca-profundo">
                                <i class="bi bi-card-checklist" aria-hidden="true"></i>
                                {{ $estado === 'aprobado' ? 'Ver mi resultado aprobado' : 'Ver mi último resultado' }}
                            </a>
                        @endif
                    </div>
                </section>
            @endif

            @if ($puedeDescargarResumen)
                <a href="{{ route('modulos.resumen', $modulo) }}" class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl border border-marca-azul/20 bg-white px-5 py-3 font-semibold text-marca-azul shadow-sm hover:bg-marca-azul/5 focus:outline-none focus:ring-2 focus:ring-marca-dorado focus:ring-offset-2">
                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                    Descargar resumen del módulo
                </a>
            @endif
        </div>
    </div>
</x-app-layout>
