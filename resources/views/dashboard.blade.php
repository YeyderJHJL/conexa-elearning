<x-app-layout>
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Mis áreas
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($areas->isEmpty())
                <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
                    <i class="bi bi-journal-bookmark text-4xl text-blue-600" aria-hidden="true"></i>
                    <h3 class="mt-3 text-lg font-semibold text-gray-900">Aún no tienes áreas asignadas</h3>
                    <p class="mt-1 text-gray-600">
                        Cuando el administrador te asigne tus áreas de capacitación, aparecerán aquí.
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                    @foreach ($areas as $area)
                        <article class="flex flex-col bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                            <div class="flex items-center gap-4">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 text-2xl">
                                    <i class="bi {{ $area->icono ?: 'bi-book' }}" aria-hidden="true"></i>
                                </span>
                                <h3 class="text-lg font-semibold text-gray-900 leading-snug">{{ $area->nombre }}</h3>
                            </div>

                            @if ($area->descripcion)
                                <p class="mt-3 text-sm text-gray-600">{{ $area->descripcion }}</p>
                            @endif

                            <div class="mt-4 pt-4 border-t border-gray-100 text-sm text-gray-500">
                                <i class="bi bi-collection" aria-hidden="true"></i>
                                {{ trans_choice(':count módulo|:count módulos', $area->modulos_activos_count) }}
                            </div>

                            {{-- Aquí irán luego el % de progreso y el enlace al área. --}}
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
