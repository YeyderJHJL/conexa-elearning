<x-app-layout titulo="Inicio">
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            @php
                $primerNombre = \Illuminate\Support\Str::of(auth()->user()->name)->before(' ')->toString();
            @endphp

            {{-- Saludo + avance global en un panel destacado --}}
            <section class="aparece relative overflow-hidden rounded-3xl bg-marca-azul p-6 text-white shadow-tarjeta-hover sm:p-8" aria-label="Avance global">
                <svg class="pointer-events-none absolute -bottom-10 -right-16 h-72 w-[30rem] max-w-none opacity-[0.14]" viewBox="0 0 540 330" aria-hidden="true">
                    <path d="M0 330 C 140 300 380 180 540 0 C 420 150 190 290 0 330 Z" fill="{{ config('marca.colores.dorado') }}" />
                </svg>

                <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-marca-gris-claro">Bienvenido a tu capacitación</p>
                        <h1 class="mt-1 break-words text-3xl font-semibold tracking-tight sm:text-4xl">Hola, {{ $primerNombre }}</h1>

                        @if ($areas->isNotEmpty())
                            <p class="mt-2 max-w-md text-marca-gris-claro">
                                @if ($progresoGlobal >= 100)
                                    ¡Completaste todas tus áreas! Gracias por tu dedicación.
                                @elseif ($progresoGlobal > 0)
                                    Vas muy bien: llevas el <span class="font-semibold text-white">{{ $progresoGlobal }}%</span> de tu capacitación.
                                @else
                                    Elige un área para empezar. Avanzarás paso a paso, a tu ritmo.
                                @endif
                            </p>
                        @endif
                    </div>

                    @if ($areas->isNotEmpty())
                        <div class="flex items-center gap-4 sm:flex-col sm:items-center sm:gap-2">
                            <x-anillo-progreso :valor="$progresoGlobal" :tamano="96" :grosor="9" :mostrar="false" etiqueta="Avance global" class="text-white" />
                            <div class="sm:text-center">
                                <p class="text-4xl font-bold leading-none text-marca-dorado">{{ $progresoGlobal }}%</p>
                                <p class="mt-1 text-xs uppercase tracking-wide text-marca-gris-claro">Tu avance global</p>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            @if ($areas->isEmpty())
                <div class="tarjeta p-8 text-center sm:p-12">
                    <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-marca-azul/5 text-3xl text-marca-azul">
                        <i class="bi bi-journal-bookmark" aria-hidden="true"></i>
                    </span>
                    <h3 class="mt-4 text-lg font-semibold text-marca-azul">Aún no tienes áreas asignadas</h3>
                    <p class="mt-1 text-marca-gris-texto">
                        Cuando el administrador te asigne tus áreas de capacitación, aparecerán aquí.
                    </p>
                </div>
            @else
                @if ($continuar)
                    @php
                        $esQuiz = $continuar['tipo'] === 'quiz';
                        $destino = $esQuiz ? route('quiz.show', $continuar['modulo']) : route('lecciones.show', $continuar['leccion']);
                        $titulo = $esQuiz ? 'Rendir el quiz: '.$continuar['modulo']->quiz->titulo : $continuar['leccion']->titulo;
                        $moduloTitulo = $esQuiz ? $continuar['modulo']->titulo : $continuar['leccion']->modulo->titulo;
                    @endphp
                    <a href="{{ $destino }}" class="tarjeta tarjeta-elevable group flex items-center gap-4 border-l-4 border-l-marca-dorado p-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul focus-visible:ring-offset-2 sm:p-5">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-marca-dorado text-2xl text-marca-azul">
                            <i class="bi {{ $esQuiz ? 'bi-patch-question-fill' : 'bi-play-fill' }}" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-marca-gris-texto">Continuar donde quedaste</p>
                            <p class="mt-0.5 line-clamp-2 break-words font-semibold text-marca-azul">{{ $titulo }}</p>
                            <p class="line-clamp-1 break-words text-sm text-marca-gris-texto">{{ $moduloTitulo }}</p>
                        </div>
                        <i class="bi bi-arrow-right-circle-fill shrink-0 text-3xl text-marca-azul transition group-hover:translate-x-1 motion-reduce:transition-none" aria-hidden="true"></i>
                    </a>
                @endif

                <section aria-labelledby="titulo-areas">
                    <h2 id="titulo-areas" class="mb-4 text-xl font-semibold tracking-tight text-marca-azul sm:text-2xl">Mis áreas</h2>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                        @foreach ($areas as $area)
                            @php
                                $porcentaje = $progresoAreas[$area->id] ?? 0;
                                $completa = $porcentaje >= 100;
                            @endphp
                            <article
                                style="--acento: {{ $area->color_acento }}; animation-delay: {{ min($loop->index, 8) * 60 }}ms"
                                class="tarjeta tarjeta-elevable aparece group relative flex flex-col overflow-hidden"
                            >
                                <div class="relative">
                                    <x-portada :imagen="$area->imagen_url" :color="$area->color_acento" :icono="$area->icono" class="h-36 sm:h-40" />
                                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-marca-azul/90 via-marca-azul/25 to-transparent"></div>

                                    @if ($completa)
                                        <span class="insignia absolute right-3 top-3 bg-marca-dorado text-marca-azul shadow">
                                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Completada
                                        </span>
                                    @endif

                                    <h3 class="absolute inset-x-4 bottom-3 break-words text-lg font-semibold leading-snug text-white drop-shadow">{{ $area->nombre }}</h3>
                                </div>

                                <div class="flex flex-1 flex-col p-5">
                                    @if ($area->descripcion)
                                        <p class="line-clamp-2 text-sm text-marca-gris-texto">{{ $area->descripcion }}</p>
                                    @endif

                                    <div class="mt-auto pt-4">
                                        <div class="flex items-center justify-between text-sm text-marca-gris-texto">
                                            <span>
                                                <i class="bi bi-collection" aria-hidden="true"></i>
                                                {{ trans_choice(':count módulo|:count módulos', $area->modulos_activos_count) }}
                                            </span>
                                            <span class="text-base font-bold text-marca-azul">{{ $porcentaje }}%</span>
                                        </div>
                                        <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-marca-azul/10" role="progressbar" aria-label="Avance de {{ $area->nombre }}" aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100">
                                            <div class="h-full rounded-full bg-marca-dorado transition-[width] duration-700 motion-reduce:transition-none" style="width: {{ $porcentaje }}%"></div>
                                        </div>

                                        <a href="{{ route('areas.show', $area) }}" class="btn {{ $completa ? 'btn-contorno' : 'btn-azul' }} mt-5 w-full after:absolute after:inset-0 after:content-['']">
                                            {{ $completa ? 'Repasar' : ($porcentaje > 0 ? 'Continuar' : 'Comenzar') }}
                                            <i class="bi bi-arrow-right transition group-hover:translate-x-0.5 motion-reduce:transition-none" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
