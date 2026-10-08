<x-app-layout>
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $modulo->titulo }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ route('areas.show', $area) }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Volver a {{ $area->nombre }}
            </a>

            @if ($modulo->descripcion)
                <p class="mt-4 text-gray-600">{{ $modulo->descripcion }}</p>
            @endif

            @if ($lecciones->isEmpty())
                <div class="mt-6 bg-white rounded-2xl shadow-sm p-8 text-center">
                    <i class="bi bi-journal-text text-4xl text-blue-600" aria-hidden="true"></i>
                    <h3 class="mt-3 text-lg font-semibold text-gray-900">Este módulo aún no tiene lecciones</h3>
                    <p class="mt-1 text-gray-600">Vuelve pronto: el contenido se publicará aquí.</p>
                </div>
            @else
                <ol class="mt-6 space-y-3">
                    @foreach ($lecciones as $leccion)
                        @php $completada = $completadas->contains($leccion->id); @endphp
                        <li>
                            <a href="{{ route('lecciones.show', $leccion) }}" class="flex items-center gap-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 transition hover:border-blue-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 font-semibold">
                                    {{ $loop->iteration }}
                                </span>

                                <div class="min-w-0 flex-1">
                                    <h3 class="text-base font-semibold text-gray-900 leading-snug">{{ $leccion->titulo }}</h3>
                                    @if ($leccion->duracion_min)
                                        <p class="mt-1 text-sm text-gray-500">
                                            <i class="bi bi-clock" aria-hidden="true"></i> {{ $leccion->duracion_min }} min
                                        </p>
                                    @endif
                                </div>

                                @if ($completada)
                                    <i class="bi bi-check-circle-fill text-2xl text-emerald-600" title="Completada" aria-hidden="true"></i>
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

            {{-- Aquí irá luego el acceso al quiz del módulo. --}}
        </div>
    </div>
</x-app-layout>
