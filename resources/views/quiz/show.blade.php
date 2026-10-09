<x-app-layout>
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-marca-azul leading-tight">
            {{ $quiz->titulo }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ route('modulos.show', $modulo) }}" class="inline-flex items-center gap-2 text-sm font-medium text-marca-azul hover:text-marca-profundo">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Volver a {{ $modulo->titulo }}
            </a>

            <p class="mt-4 text-sm text-marca-gris">
                <i class="bi bi-patch-question" aria-hidden="true"></i>
                {{ trans_choice(':count pregunta|:count preguntas', $quiz->preguntas->count()) }}
                · Necesitas al menos <span class="font-semibold">{{ $notaMinima }}%</span> para aprobar. Puedes reintentar las veces que quieras.
            </p>

            @if ($errors->any())
                <div role="alert" class="mt-4 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <i class="bi bi-exclamation-circle-fill mt-0.5" aria-hidden="true"></i>
                    <span>Responde todas las preguntas para enviar tu quiz.</span>
                </div>
            @endif

            <form method="POST" action="{{ route('quiz.enviar', $modulo) }}" class="mt-6 space-y-4">
                @csrf

                @foreach ($quiz->preguntas as $pregunta)
                    @php $sinResponder = $errors->has("respuestas.{$pregunta->id}"); @endphp
                    <fieldset class="rounded-2xl bg-white p-4 shadow-sm border sm:p-5 {{ $sinResponder ? 'border-red-300' : 'border-gray-100' }}">
                        <legend class="sr-only">Pregunta {{ $loop->iteration }}</legend>

                        <div class="flex items-start gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-marca-azul/5 text-sm font-semibold text-marca-azul">
                                {{ $loop->iteration }}
                            </span>
                            <p class="font-semibold text-marca-azul leading-snug">{{ $pregunta->enunciado }}</p>
                        </div>

                        <div class="mt-4 space-y-2">
                            @foreach ($pregunta->opciones as $opcion)
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-3 transition hover:border-marca-dorado has-[:checked]:border-marca-azul has-[:checked]:bg-marca-azul/5">
                                    <input
                                        type="radio"
                                        name="respuestas[{{ $pregunta->id }}]"
                                        value="{{ $opcion->id }}"
                                        class="mt-0.5 h-5 w-5 shrink-0 border-gray-300 text-marca-azul focus:ring-marca-dorado"
                                        @checked((string) old("respuestas.{$pregunta->id}") === (string) $opcion->id)
                                        required
                                    >
                                    <span class="text-sm text-marca-azul sm:text-base">{{ $opcion->texto }}</span>
                                </label>
                            @endforeach
                        </div>

                        @if ($sinResponder)
                            <p class="mt-3 text-sm text-red-600">Responde esta pregunta.</p>
                        @endif
                    </fieldset>
                @endforeach

                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-marca-azul px-5 py-3 font-semibold text-white shadow-sm hover:bg-marca-profundo focus:outline-none focus:ring-2 focus:ring-marca-dorado focus:ring-offset-2">
                    <i class="bi bi-send-check" aria-hidden="true"></i>
                    Enviar respuestas
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
