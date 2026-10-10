<x-app-layout :titulo="$leccion->titulo">
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('modulos.show', $modulo) }}" class="inline-flex items-center gap-2 rounded text-sm font-semibold text-marca-azul transition hover:-translate-x-0.5 hover:text-marca-complementario motion-reduce:transition-none">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Volver a {{ $modulo->titulo }}
            </a>

            <header class="aparece">
                <p class="text-xs font-semibold uppercase tracking-wide text-marca-gris-texto">{{ $modulo->titulo }}</p>
                <h1 class="mt-1 break-words text-3xl font-semibold leading-tight tracking-tight text-marca-azul sm:text-4xl">{{ $leccion->titulo }}</h1>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @if ($leccion->duracion_min)
                        <span class="insignia bg-marca-azul/5 text-marca-azul"><i class="bi bi-clock" aria-hidden="true"></i> {{ $leccion->duracion_min }} min</span>
                    @endif
                    @if ($completada)
                        <span class="insignia bg-marca-dorado text-marca-azul"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Completada</span>
                    @endif
                </div>
            </header>

            @if (filled($leccion->archivo_video))
                {{-- Video subido desde el computador: se reproduce aquí mismo, con adelantar/retroceder. --}}
                <div class="overflow-hidden rounded-2xl bg-marca-azul shadow-tarjeta-hover ring-1 ring-marca-azul/10" data-video-subido>
                    <video
                        controls
                        playsinline
                        preload="metadata"
                        controlsList="nodownload"
                        aria-label="{{ $leccion->titulo }}"
                        class="aspect-video w-full bg-black"
                        src="{{ route('lecciones.video', $leccion) }}"
                    >
                        Tu navegador no puede reproducir este video.
                    </video>
                </div>
            @endif

            @if (blank($leccion->archivo_video) && $leccion->url_video)
                @if ($leccion->video_embed_url)
                    <div class="aspect-video overflow-hidden rounded-2xl bg-marca-azul shadow-tarjeta-hover ring-1 ring-marca-azul/10">
                        <iframe
                            src="{{ $leccion->video_embed_url }}"
                            title="{{ $leccion->titulo }}"
                            class="h-full w-full"
                            loading="lazy"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    </div>
                @elseif ($leccion->video_directo_url)
                    <div class="overflow-hidden rounded-2xl bg-marca-azul shadow-tarjeta-hover ring-1 ring-marca-azul/10" data-video-directo>
                        <video controls playsinline preload="metadata" aria-label="{{ $leccion->titulo }}" class="aspect-video w-full bg-black" src="{{ $leccion->video_directo_url }}">
                            Tu navegador no puede reproducir este video.
                        </video>
                    </div>
                @elseif ($enlaceVideo = \Illuminate\Support\Str::sanitizeUrl($leccion->url_video))
                    <a href="{{ $enlaceVideo }}" target="_blank" rel="noopener noreferrer" class="tarjeta tarjeta-elevable flex items-center gap-4 p-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul focus-visible:ring-offset-2">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-marca-azul text-2xl text-marca-dorado">
                            <i class="bi bi-play-fill" aria-hidden="true"></i>
                        </span>
                        <span class="font-semibold text-marca-azul">Ver el video de esta lección</span>
                    </a>
                @endif
            @endif

            @if (filled($leccion->contenido))
                <article class="tarjeta p-6 sm:p-9">
                    <div class="contenido-rico">
                        {!! \Illuminate\Support\Str::sanitizeHtml($leccion->contenido) !!}
                    </div>
                </article>
            @endif

            @if (filled($leccion->archivo_pdf))
                <section class="tarjeta flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-marca-azul text-3xl text-marca-dorado">
                            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h3 class="font-semibold text-marca-azul">Material en PDF</h3>
                            <p class="text-sm text-marca-gris-texto">Documento adjunto a esta lección</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('lecciones.pdf', $leccion) }}" target="_blank" rel="noopener" class="btn btn-azul flex-1 !px-4 !py-2 text-sm sm:flex-none">
                            <i class="bi bi-eye" aria-hidden="true"></i> Ver
                        </a>
                        <a href="{{ route('lecciones.pdf', ['leccion' => $leccion, 'descargar' => 1]) }}" class="btn btn-contorno flex-1 !px-4 !py-2 text-sm sm:flex-none">
                            <i class="bi bi-download" aria-hidden="true"></i> Descargar
                        </a>
                    </div>
                </section>
            @endif

            @php
                $sinContenido = blank($leccion->url_video)
                    && blank($leccion->archivo_video)
                    && blank($leccion->archivo_pdf)
                    && trim(strip_tags((string) $leccion->contenido)) === ''
                    && ! str_contains((string) $leccion->contenido, '<img');
            @endphp

            @if ($sinContenido)
                <div class="tarjeta p-8 text-center">
                    <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-marca-azul/5 text-3xl text-marca-azul">
                        <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                    </span>
                    <h3 class="mt-4 text-lg font-semibold text-marca-azul">El contenido de esta lección llegará pronto</h3>
                    <p class="mt-1 text-marca-gris-texto">Todavía no hay texto, video ni material para mostrar aquí. Vuelve más tarde o continúa con las demás lecciones.</p>
                </div>
            @endif

            <form method="POST" action="{{ route('lecciones.completar', $leccion) }}">
                @csrf
                <button type="submit" class="btn btn-azul w-full py-4">
                    <i class="bi bi-check2-circle text-xl" aria-hidden="true"></i>
                    @if ($completada)
                        Completada · {{ $siguiente ? 'continuar' : 'volver al módulo' }}
                    @else
                        Marcar como completada y {{ $siguiente ? 'continuar' : 'terminar' }}
                    @endif
                </button>
            </form>

            <nav class="grid grid-cols-2 gap-3 text-sm" aria-label="Navegación entre lecciones">
                @if ($anterior)
                    <a href="{{ route('lecciones.show', $anterior) }}" class="tarjeta tarjeta-elevable flex items-center gap-2 px-4 py-3 font-medium text-marca-azul focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul focus-visible:ring-offset-2">
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        <span class="truncate" title="{{ $anterior->titulo }}">{{ $anterior->titulo }}</span>
                    </a>
                @else
                    <span></span>
                @endif

                @if ($siguiente)
                    <a href="{{ route('lecciones.show', $siguiente) }}" class="tarjeta tarjeta-elevable flex items-center justify-end gap-2 px-4 py-3 font-medium text-marca-azul focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul focus-visible:ring-offset-2">
                        <span class="truncate" title="{{ $siguiente->titulo }}">{{ $siguiente->titulo }}</span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                @endif
            </nav>
        </div>
    </div>
</x-app-layout>
