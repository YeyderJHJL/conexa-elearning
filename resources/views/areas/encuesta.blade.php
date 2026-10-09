<x-app-layout>
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Encuesta de satisfacción
        </h2>
    </x-slot>

    @php
        $criterios = [
            'claridad' => ['¿Qué tan claro fue el contenido?', 'Poco claro', 'Muy claro'],
            'utilidad' => ['¿Qué tan útil te resultó para tu trabajo?', 'Poco útil', 'Muy útil'],
            'ritmo' => ['¿Qué tan adecuado fue el ritmo?', 'Nada adecuado', 'Muy adecuado'],
        ];
    @endphp

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ route('areas.show', $area) }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Volver a {{ $area->nombre }}
            </a>

            <p class="mt-4 text-gray-600">
                ¡Completaste <span class="font-semibold text-gray-900">{{ $area->nombre }}</span>! Cuéntanos cómo te fue: son 3 preguntas rápidas y un comentario opcional.
            </p>

            <form method="POST" action="{{ route('areas.feedback', $area) }}" class="mt-6 space-y-4">
                @csrf

                @foreach ($criterios as $campo => [$pregunta, $minimo, $maximo])
                    <fieldset class="rounded-2xl bg-white p-4 shadow-sm border sm:p-5 {{ $errors->has($campo) ? 'border-red-300' : 'border-gray-100' }}">
                        <legend class="sr-only">{{ $pregunta }}</legend>
                        <p class="font-semibold text-gray-900">{{ $pregunta }}</p>

                        <div class="mt-3 grid grid-cols-5 gap-2">
                            @foreach (range(1, 5) as $nota)
                                <label class="flex cursor-pointer items-center justify-center rounded-xl border border-gray-200 py-3 text-sm font-semibold text-gray-700 transition hover:border-blue-300 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-600 has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-blue-500 has-[:focus-visible]:ring-offset-2">
                                    <input type="radio" name="{{ $campo }}" value="{{ $nota }}" class="sr-only" @checked((string) old($campo) === (string) $nota) required>
                                    {{ $nota }}
                                </label>
                            @endforeach
                        </div>
                        <div class="mt-2 flex justify-between text-xs text-gray-500">
                            <span>1 · {{ $minimo }}</span>
                            <span>5 · {{ $maximo }}</span>
                        </div>

                        @error($campo)
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </fieldset>
                @endforeach

                <div class="rounded-2xl bg-white p-4 shadow-sm border border-gray-100 sm:p-5">
                    <label for="comentario" class="font-semibold text-gray-900">Comentario <span class="text-sm font-normal text-gray-500">(opcional)</span></label>
                    <textarea id="comentario" name="comentario" rows="4" maxlength="600" class="mt-3 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="¿Qué mejorarías o qué te gustó más?">{{ old('comentario') }}</textarea>
                    @error('comentario')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="bi bi-send-check" aria-hidden="true"></i>
                    Enviar mi opinión
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
