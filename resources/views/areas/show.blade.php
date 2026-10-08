<x-app-layout>
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $area->nombre }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Volver al inicio
            </a>

            @if ($area->descripcion)
                <p class="mt-4 text-gray-600">{{ $area->descripcion }}</p>
            @endif

            @if ($modulos->isEmpty())
                <div class="mt-6 bg-white rounded-2xl shadow-sm p-8 text-center">
                    <i class="bi bi-collection text-4xl text-blue-600" aria-hidden="true"></i>
                    <h3 class="mt-3 text-lg font-semibold text-gray-900">Esta área aún no tiene módulos</h3>
                    <p class="mt-1 text-gray-600">Vuelve pronto: el contenido se publicará aquí.</p>
                </div>
            @else
                <ol class="mt-6 space-y-3 sm:space-y-4">
                    @foreach ($modulos as $modulo)
                        @php $bloqueado = $modulo->estado === 'bloqueado'; @endphp
                        <li>
                        <a href="{{ route('modulos.show', $modulo) }}" class="flex items-start gap-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 transition hover:border-blue-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 {{ $bloqueado ? 'opacity-70' : '' }}">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl font-semibold {{ $bloqueado ? 'bg-gray-100 text-gray-500' : 'bg-blue-50 text-blue-600' }}">
                                {{ $loop->iteration }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 leading-snug">{{ $modulo->titulo }}</h3>

                                    @if ($bloqueado)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                                            <i class="bi bi-lock-fill" aria-hidden="true"></i> Bloqueado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                            <i class="bi bi-unlock" aria-hidden="true"></i> Disponible
                                        </span>
                                    @endif
                                </div>

                                @if ($modulo->descripcion)
                                    <p class="mt-1 text-sm text-gray-600">{{ $modulo->descripcion }}</p>
                                @endif

                                <p class="mt-3 text-sm text-gray-500">
                                    <i class="bi bi-journal-text" aria-hidden="true"></i>
                                    {{ trans_choice(':count lección|:count lecciones', $modulo->lecciones_activas_count) }}
                                </p>

                                {{-- Aquí irá luego el progreso real del módulo. --}}
                            </div>
                        </a>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>
</x-app-layout>
