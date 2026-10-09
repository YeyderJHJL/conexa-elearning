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

            @if (session('aviso'))
                <div role="alert" class="mt-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <i class="bi bi-lock-fill mt-0.5" aria-hidden="true"></i>
                    <span>{{ session('aviso') }}</span>
                </div>
            @endif

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
                        @php
                            $estado = $estadosModulos[$modulo->id] ?? 'bloqueado';
                            $porcentaje = $progresoModulos[$modulo->id] ?? 0;
                            $completo = $estado === 'completado';
                            $bloqueado = $estado === 'bloqueado';
                            $accesible = ! $bloqueado || $esAdmin;
                        @endphp
                        <li>
                            <a
                                @if ($accesible) href="{{ route('modulos.show', $modulo) }}" @else aria-disabled="true" @endif
                                class="flex items-start gap-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 {{ $accesible ? 'transition hover:border-blue-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500' : 'cursor-not-allowed' }} {{ $bloqueado ? 'opacity-70' : '' }}"
                            >
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl font-semibold {{ $completo ? 'bg-emerald-50 text-emerald-600' : ($bloqueado ? 'bg-gray-100 text-gray-500' : 'bg-blue-50 text-blue-600') }}">
                                    @if ($completo)
                                        <i class="bi bi-check-lg text-xl" aria-hidden="true"></i>
                                    @elseif ($bloqueado)
                                        <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 leading-snug">{{ $modulo->titulo }}</h3>

                                        @if ($completo)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Completado
                                            </span>
                                        @elseif ($bloqueado)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                                                <i class="bi bi-lock-fill" aria-hidden="true"></i> Bloqueado
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                                <i class="bi bi-play-circle" aria-hidden="true"></i> En curso
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

                                    <div class="mt-3 flex items-center gap-3">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100">
                                            <div class="h-full rounded-full {{ $completo ? 'bg-emerald-500' : 'bg-blue-600' }}" style="width: {{ $porcentaje }}%"></div>
                                        </div>
                                        <span class="text-sm font-semibold {{ $completo ? 'text-emerald-600' : 'text-blue-600' }}">{{ $porcentaje }}%</span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ol>
            @endif

            @if ($puedeDescargarResumen)
                <a href="{{ route('areas.resumen', $area) }}" class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl border border-blue-200 bg-white px-5 py-3 font-semibold text-blue-700 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                    Descargar resumen del área
                </a>
            @endif
        </div>
    </div>
</x-app-layout>
