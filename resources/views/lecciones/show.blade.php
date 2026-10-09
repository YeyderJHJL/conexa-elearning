<x-app-layout>
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-marca-azul leading-tight">
            {{ $leccion->titulo }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <a href="{{ route('modulos.show', $modulo) }}" class="inline-flex items-center gap-2 text-sm font-medium text-marca-azul hover:text-marca-profundo">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Volver a {{ $modulo->titulo }}
            </a>

            @if ($leccion->url_video)
                @if ($leccion->video_embed_url)
                    <div class="aspect-video overflow-hidden rounded-2xl bg-black shadow-sm">
                        <iframe
                            src="{{ $leccion->video_embed_url }}"
                            title="{{ $leccion->titulo }}"
                            class="h-full w-full"
                            loading="lazy"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    </div>
                @elseif ($enlaceVideo = \Illuminate\Support\Str::sanitizeUrl($leccion->url_video))
                    <a href="{{ $enlaceVideo }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm border border-gray-100 text-marca-azul hover:border-marca-dorado">
                        <i class="bi bi-play-circle-fill text-2xl" aria-hidden="true"></i>
                        <span class="font-medium">Ver el video de esta lección</span>
                    </a>
                @endif
            @endif

            @if (filled($leccion->contenido))
                <article class="rounded-2xl bg-white p-5 sm:p-6 shadow-sm border border-gray-100">
                    <div class="contenido-rico">
                        {!! \Illuminate\Support\Str::sanitizeHtml($leccion->contenido) !!}
                    </div>
                </article>
            @endif

            @if (filled($leccion->archivo_pdf))
                <section class="flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-sm border border-gray-100 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-marca-azul/5 text-2xl text-marca-azul">
                            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h3 class="font-semibold text-marca-azul">Material en PDF</h3>
                            <p class="text-sm text-marca-gris">Documento adjunto a esta lección</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('lecciones.pdf', $leccion) }}" target="_blank" rel="noopener" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-marca-azul px-4 py-2 text-sm font-medium text-white hover:bg-marca-profundo sm:flex-none">
                            <i class="bi bi-eye" aria-hidden="true"></i> Ver
                        </a>
                        <a href="{{ route('lecciones.pdf', ['leccion' => $leccion, 'descargar' => 1]) }}" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-marca-azul/20 px-4 py-2 text-sm font-medium text-marca-azul hover:bg-marca-azul/5 sm:flex-none">
                            <i class="bi bi-download" aria-hidden="true"></i> Descargar
                        </a>
                    </div>
                </section>
            @endif

            <form method="POST" action="{{ route('lecciones.completar', $leccion) }}">
                @csrf
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-marca-azul px-5 py-3 font-semibold text-white shadow-sm hover:bg-marca-profundo focus:outline-none focus:ring-2 focus:ring-marca-dorado focus:ring-offset-2">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    @if ($completada)
                        Completada · {{ $siguiente ? 'continuar' : 'volver al módulo' }}
                    @else
                        Marcar como completada y {{ $siguiente ? 'continuar' : 'terminar' }}
                    @endif
                </button>
            </form>

            <nav class="grid grid-cols-2 gap-3 text-sm" aria-label="Navegación entre lecciones">
                @if ($anterior)
                    <a href="{{ route('lecciones.show', $anterior) }}" class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-700 hover:border-marca-dorado">
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        <span class="truncate">{{ $anterior->titulo }}</span>
                    </a>
                @else
                    <span></span>
                @endif

                @if ($siguiente)
                    <a href="{{ route('lecciones.show', $siguiente) }}" class="flex items-center justify-end gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-700 hover:border-marca-dorado">
                        <span class="truncate">{{ $siguiente->titulo }}</span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                @endif
            </nav>
        </div>
    </div>
</x-app-layout>
