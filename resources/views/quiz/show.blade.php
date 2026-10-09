<x-app-layout :titulo="$quiz->titulo">
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('modulos.show', $modulo) }}" class="inline-flex items-center gap-2 rounded text-sm font-semibold text-marca-azul transition hover:-translate-x-0.5 hover:text-marca-complementario motion-reduce:transition-none">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Volver a {{ $modulo->titulo }}
            </a>

            <header class="aparece relative mt-4 overflow-hidden rounded-3xl bg-marca-azul p-6 text-white shadow-tarjeta-hover sm:p-8">
                <svg class="pointer-events-none absolute -bottom-8 -right-12 h-56 w-96 max-w-none opacity-[0.14]" viewBox="0 0 540 330" aria-hidden="true">
                    <path d="M0 330 C 140 300 380 180 540 0 C 420 150 190 290 0 330 Z" fill="{{ config('marca.colores.dorado') }}" />
                </svg>

                <div class="relative">
                    <p class="text-xs font-semibold uppercase tracking-wide text-marca-gris-claro">{{ $modulo->titulo }}</p>
                    <h1 class="mt-1 break-words text-2xl font-semibold tracking-tight sm:text-3xl">{{ $quiz->titulo }}</h1>
                    <p class="mt-3 text-sm text-marca-gris-claro">
                        <i class="bi bi-patch-question" aria-hidden="true"></i>
                        {{ trans_choice(':count pregunta|:count preguntas', $quiz->preguntas->count()) }}
                        · Necesitas al menos <span class="font-semibold text-white">{{ $notaMinima }}%</span> para aprobar. Puedes reintentar las veces que quieras.
                    </p>
                </div>
            </header>

            @if ($errors->any())
                <div role="alert" class="mt-4 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <i class="bi bi-exclamation-circle-fill mt-0.5" aria-hidden="true"></i>
                    <span>Responde todas las preguntas para enviar tu quiz.</span>
                </div>
            @endif

            <form method="POST" action="{{ route('quiz.enviar', $modulo) }}" class="mt-6 space-y-5">
                @csrf

                @foreach ($quiz->preguntas as $pregunta)
                    @php $sinResponder = $errors->has("respuestas.{$pregunta->id}"); @endphp
                    <fieldset class="tarjeta p-5 sm:p-6 {{ $sinResponder ? '!border-red-300' : '' }}">
                        <legend class="sr-only">Pregunta {{ $loop->iteration }}</legend>

                        <div class="flex items-start gap-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-marca-azul text-sm font-bold text-white">
                                {{ $loop->iteration }}
                            </span>
                            <p class="pt-1 text-base font-semibold leading-snug text-marca-azul break-words sm:text-lg">{{ $pregunta->enunciado }}</p>
                        </div>

                        <div class="mt-4 space-y-2.5">
                            @foreach ($pregunta->opciones as $opcion)
                                <label class="group flex cursor-pointer items-center gap-3 rounded-2xl border-2 border-marca-azul/10 bg-white p-3.5 transition duration-150 hover:border-marca-complementario hover:bg-marca-azul/[0.03] has-[:checked]:border-marca-azul has-[:checked]:bg-marca-azul/5 has-[:checked]:shadow-sm has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-marca-azul has-[:focus-visible]:ring-offset-2 motion-reduce:transition-none">
                                    <input
                                        type="radio"
                                        name="respuestas[{{ $pregunta->id }}]"
                                        value="{{ $opcion->id }}"
                                        class="peer sr-only"
                                        @checked((string) old("respuestas.{$pregunta->id}") === (string) $opcion->id)
                                        required
                                    >
                                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full border-2 border-marca-gris-claro text-transparent transition peer-checked:border-marca-dorado peer-checked:bg-marca-dorado peer-checked:text-marca-azul motion-reduce:transition-none" aria-hidden="true">
                                        <i class="bi bi-check-lg text-sm"></i>
                                    </span>
                                    <span class="text-sm text-marca-azul sm:text-base">{{ $opcion->texto }}</span>
                                </label>
                            @endforeach
                        </div>

                        @if ($sinResponder)
                            <p class="mt-3 text-sm font-medium text-red-600">Responde esta pregunta.</p>
                        @endif
                    </fieldset>
                @endforeach

                <button type="submit" class="btn btn-azul w-full py-4">
                    <i class="bi bi-send-check" aria-hidden="true"></i>
                    Enviar respuestas
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
