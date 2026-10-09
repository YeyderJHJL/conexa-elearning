<x-app-layout>
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-marca-azul leading-tight">
            Resultado: {{ $intento->quiz->titulo }}
        </h2>
    </x-slot>

    @php
        $aprobado = $intento->aprobado;
        $correctas = $detalle['total'] - $detalle['falladas']->count();
    @endphp

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-2xl bg-white p-6 text-center shadow-sm border border-gray-100" aria-label="Resultado">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full text-3xl {{ $aprobado ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">
                    <i class="bi {{ $aprobado ? 'bi-trophy-fill' : 'bi-arrow-repeat' }}" aria-hidden="true"></i>
                </span>

                <p class="mt-4 text-5xl font-bold text-marca-azul">{{ $intento->puntaje }}%</p>

                <p class="mt-3">
                    @if ($aprobado)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Aprobado
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-3 py-1 text-sm font-medium text-amber-700">
                            <i class="bi bi-x-circle-fill" aria-hidden="true"></i> No aprobado
                        </span>
                    @endif
                </p>

                <p class="mt-3 text-sm text-marca-gris">
                    {{ $correctas }} de {{ $detalle['total'] }} {{ $detalle['total'] === 1 ? 'respuesta correcta' : 'respuestas correctas' }}
                    · Nota mínima: {{ $notaMinima }}%
                </p>
            </section>

            @if ($detalle['falladas']->isNotEmpty())
                <section class="space-y-3" aria-label="Preguntas falladas">
                    <h3 class="font-semibold text-marca-azul">Preguntas que debes repasar</h3>

                    @foreach ($detalle['falladas'] as $fallada)
                        <article class="rounded-2xl bg-white p-4 shadow-sm border border-gray-100 sm:p-5">
                            <p class="font-medium text-marca-azul leading-snug">{{ $fallada['enunciado'] }}</p>

                            <p class="mt-3 flex items-start gap-2 text-sm text-red-700">
                                <i class="bi bi-x-circle-fill mt-0.5 shrink-0" aria-hidden="true"></i>
                                <span><span class="font-semibold">Tu respuesta:</span> {{ $fallada['elegida'] ?? 'Sin respuesta' }}</span>
                            </p>

                            @if ($fallada['correcta'])
                                <p class="mt-2 flex items-start gap-2 text-sm text-emerald-700">
                                    <i class="bi bi-check-circle-fill mt-0.5 shrink-0" aria-hidden="true"></i>
                                    <span><span class="font-semibold">Respuesta correcta:</span> {{ $fallada['correcta'] }}</span>
                                </p>
                            @endif
                        </article>
                    @endforeach
                </section>
            @else
                <p class="rounded-2xl bg-emerald-50 p-4 text-center text-sm font-medium text-emerald-800">
                    ¡Respondiste todo correctamente!
                </p>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('quiz.show', $modulo) }}" class="flex flex-1 items-center justify-center gap-2 rounded-xl px-5 py-3 font-semibold focus:outline-none focus:ring-2 focus:ring-marca-dorado focus:ring-offset-2 {{ $aprobado ? 'border border-marca-azul/20 text-marca-azul hover:bg-marca-azul/5' : 'bg-marca-azul text-white shadow-sm hover:bg-marca-profundo' }}">
                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                    Reintentar
                </a>
                <a href="{{ route('modulos.show', $modulo) }}" class="flex flex-1 items-center justify-center gap-2 rounded-xl px-5 py-3 font-semibold focus:outline-none focus:ring-2 focus:ring-marca-dorado focus:ring-offset-2 {{ $aprobado ? 'bg-marca-azul text-white shadow-sm hover:bg-marca-profundo' : 'border border-marca-azul/20 text-marca-azul hover:bg-marca-azul/5' }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    Volver al módulo
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
