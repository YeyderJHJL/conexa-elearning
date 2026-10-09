<x-app-layout titulo="Encuesta de satisfacción">
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    @php
        $criterios = [
            'claridad' => ['¿Qué tan claro fue el contenido?', 'Poco claro', 'Muy claro'],
            'utilidad' => ['¿Qué tan útil te resultó para tu trabajo?', 'Poco útil', 'Muy útil'],
            'ritmo' => ['¿Qué tan adecuado fue el ritmo?', 'Nada adecuado', 'Muy adecuado'],
        ];
    @endphp

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('areas.show', $area) }}" class="inline-flex items-center gap-2 rounded text-sm font-semibold text-marca-azul transition hover:-translate-x-0.5 hover:text-marca-complementario motion-reduce:transition-none">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Volver a {{ $area->nombre }}
            </a>

            <header class="aparece relative mt-4 overflow-hidden rounded-3xl bg-marca-azul p-6 text-white shadow-tarjeta-hover sm:p-8">
                <svg class="pointer-events-none absolute -bottom-8 -right-12 h-56 w-96 max-w-none opacity-[0.14]" viewBox="0 0 540 330" aria-hidden="true">
                    <path d="M0 330 C 140 300 380 180 540 0 C 420 150 190 290 0 330 Z" fill="{{ config('marca.colores.dorado') }}" />
                </svg>
                <div class="relative">
                    <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Encuesta de satisfacción</h1>
                    <p class="mt-2 text-marca-gris-claro">
                        ¡Completaste <span class="font-semibold text-white">{{ $area->nombre }}</span>! Cuéntanos cómo te fue: son 3 preguntas rápidas y un comentario opcional.
                    </p>
                </div>
            </header>

            <form method="POST" action="{{ route('areas.feedback', $area) }}" class="mt-6 space-y-4">
                @csrf

                @foreach ($criterios as $campo => [$pregunta, $minimo, $maximo])
                    <fieldset class="tarjeta p-5 sm:p-6 {{ $errors->has($campo) ? '!border-red-300' : '' }}">
                        <legend class="sr-only">{{ $pregunta }}</legend>
                        <p class="font-semibold text-marca-azul">{{ $pregunta }}</p>

                        <div class="mt-3 grid grid-cols-5 gap-2">
                            @foreach (range(1, 5) as $nota)
                                <label class="flex cursor-pointer items-center justify-center rounded-xl border-2 border-marca-azul/10 py-3 text-sm font-semibold text-marca-azul transition hover:border-marca-complementario has-[:checked]:border-marca-azul has-[:checked]:bg-marca-azul has-[:checked]:text-white motion-reduce:transition-none has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-marca-azul has-[:focus-visible]:ring-offset-2">
                                    <input type="radio" name="{{ $campo }}" value="{{ $nota }}" class="sr-only" @checked((string) old($campo) === (string) $nota) required>
                                    {{ $nota }}
                                </label>
                            @endforeach
                        </div>
                        <div class="mt-2 flex justify-between text-xs text-marca-gris-texto">
                            <span>1 · {{ $minimo }}</span>
                            <span>5 · {{ $maximo }}</span>
                        </div>

                        @error($campo)
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </fieldset>
                @endforeach

                <div class="tarjeta p-5 sm:p-6">
                    <label for="comentario" class="font-semibold text-marca-azul">Comentario <span class="text-sm font-normal text-marca-gris-texto">(opcional)</span></label>
                    <textarea id="comentario" name="comentario" rows="4" maxlength="600" class="mt-3 block w-full rounded-xl border-gray-300 text-base shadow-sm placeholder:text-marca-gris-texto focus:border-marca-azul focus:ring-marca-azul sm:text-sm" placeholder="¿Qué mejorarías o qué te gustó más?">{{ old('comentario') }}</textarea>
                    @error('comentario')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn-azul w-full py-4">
                    <i class="bi bi-send-check" aria-hidden="true"></i>
                    Enviar mi opinión
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
