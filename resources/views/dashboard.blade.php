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
                <section class="mb-6 rounded-2xl bg-white p-5 shadow-sm border border-gray-100 sm:mb-8" aria-label="Avance global">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Tu avance global</p>
                            <p class="mt-1 text-3xl font-bold text-gray-900">{{ $progresoGlobal }}%</p>
                        </div>
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-2xl text-blue-600">
                            <i class="bi {{ $progresoGlobal >= 100 ? 'bi-trophy' : 'bi-graph-up-arrow' }}" aria-hidden="true"></i>
                        </span>
                    </div>
                    <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-valuenow="{{ $progresoGlobal }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="h-full rounded-full bg-blue-600" style="width: {{ $progresoGlobal }}%"></div>
                    </div>
                </section>

                @if ($continuar)
                    <a href="{{ route('lecciones.show', $continuar) }}" class="mb-6 flex items-center justify-between gap-4 rounded-2xl bg-blue-600 p-5 text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mb-8">
                        <div class="min-w-0">
                            <p class="text-sm text-blue-100">Continuar donde quedaste</p>
                            <p class="mt-1 truncate font-semibold">{{ $continuar->titulo }}</p>
                            <p class="truncate text-sm text-blue-100">{{ $continuar->modulo->titulo }}</p>
                        </div>
                        <i class="bi bi-play-circle-fill shrink-0 text-4xl" aria-hidden="true"></i>
                    </a>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                    @foreach ($areas as $area)
                        <a href="{{ route('areas.show', $area) }}" class="flex flex-col bg-white rounded-2xl shadow-sm border border-gray-100 p-5 transition hover:border-blue-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <div class="flex items-center gap-4">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 text-2xl">
                                    <i class="bi {{ $area->icono ?: 'bi-book' }}" aria-hidden="true"></i>
                                </span>
                                <h3 class="text-lg font-semibold text-gray-900 leading-snug">{{ $area->nombre }}</h3>
                            </div>

                            @if ($area->descripcion)
                                <p class="mt-3 text-sm text-gray-600">{{ $area->descripcion }}</p>
                            @endif

                            @php $porcentaje = $progresoAreas[$area->id] ?? 0; @endphp
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <span>
                                        <i class="bi bi-collection" aria-hidden="true"></i>
                                        {{ trans_choice(':count módulo|:count módulos', $area->modulos_activos_count) }}
                                    </span>
                                    <span class="font-semibold {{ $porcentaje >= 100 ? 'text-emerald-600' : 'text-blue-600' }}">{{ $porcentaje }}%</span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="h-full rounded-full {{ $porcentaje >= 100 ? 'bg-emerald-500' : 'bg-blue-600' }}" style="width: {{ $porcentaje }}%"></div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
