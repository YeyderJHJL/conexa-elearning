<x-app-layout>
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    @php
        $aprobado = $intento->aprobado;
        $correctas = $detalle['total'] - $detalle['falladas']->count();
    @endphp

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <p class="text-xs font-semibold uppercase tracking-wide text-marca-gris-texto">Resultado: {{ $intento->quiz->titulo }}</p>

            <section class="aparece tarjeta relative overflow-hidden p-6 text-center sm:p-8" aria-label="Resultado">
                <div class="pointer-events-none absolute inset-x-0 top-0 h-1.5 {{ $aprobado ? 'bg-marca-dorado' : 'bg-amber-400' }}"></div>

                <span class="mx-auto grid h-16 w-16 place-items-center rounded-full text-3xl {{ $aprobado ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">
                    <i class="bi {{ $aprobado ? 'bi-trophy-fill' : 'bi-arrow-repeat' }}" aria-hidden="true"></i>
                </span>

                <p class="mt-4 text-6xl font-bold tracking-tight text-marca-azul">{{ $intento->puntaje }}%</p>

                <p class="mt-3">
                    @if ($aprobado)
                        <span class="insignia bg-emerald-50 px-3 py-1 text-sm text-emerald-700">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Aprobado
                        </span>
                    @else
                        <span class="insignia bg-amber-50 px-3 py-1 text-sm text-amber-700">
                            <i class="bi bi-x-circle-fill" aria-hidden="true"></i> No aprobado
                        </span>
                    @endif
                </p>

                <p class="mt-3 text-sm text-marca-gris-texto">
                    {{ $correctas }} de {{ $detalle['total'] }} {{ $detalle['total'] === 1 ? 'respuesta correcta' : 'respuestas correctas' }}
                    · Nota mínima: {{ $notaMinima }}%
                </p>
            </section>

            @if ($detalle['falladas']->isNotEmpty())
                <section class="space-y-3" aria-label="Preguntas falladas">
                    <h3 class="text-lg font-semibold text-marca-azul">Preguntas que debes repasar</h3>

                    @foreach ($detalle['falladas'] as $fallada)
                        <article class="tarjeta p-5">
                            <p class="font-semibold leading-snug text-marca-azul break-words">{{ $fallada['enunciado'] }}</p>

                            <p class="mt-3 flex items-start gap-2 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700">
                                <i class="bi bi-x-circle-fill mt-0.5 shrink-0" aria-hidden="true"></i>
                                <span><span class="font-semibold">Tu respuesta:</span> {{ $fallada['elegida'] ?? 'Sin respuesta' }}</span>
                            </p>

                            @if ($fallada['correcta'])
                                <p class="mt-2 flex items-start gap-2 rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
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
                <a href="{{ route('quiz.show', $modulo) }}" class="btn flex-1 {{ $aprobado ? 'btn-contorno' : 'btn-azul' }}">
                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                    Reintentar
                </a>
                <a href="{{ route('modulos.show', $modulo) }}" class="btn flex-1 {{ $aprobado ? 'btn-azul' : 'btn-contorno' }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    Volver al módulo
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
