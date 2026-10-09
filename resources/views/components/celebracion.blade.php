{{--
    Celebración puntual al aprobar un quiz o completar un módulo. Solo se muestra si la acción dejó
    `celebracion` en la sesión (se consume en la siguiente página, así que no se repite al recargar).
    El confeti es CSS puro, no recibe clics y se oculta con prefers-reduced-motion.
--}}
@php $celebracion = session('celebracion'); @endphp

@if (is_array($celebracion))
    <div x-data="{ abierta: true }" x-show="abierta" role="status" aria-live="polite" data-celebracion>
        @if ($celebracion['confeti'] ?? true)
            <div class="confeti" aria-hidden="true">
                @for ($i = 0; $i < 18; $i++)
                    <span></span>
                @endfor
            </div>
        @endif

        <div class="mx-auto max-w-3xl px-4 pt-4 sm:px-6 lg:px-8">
            <div class="flex items-start gap-3 rounded-2xl border-l-4 border-marca-dorado bg-marca-azul p-4 text-white shadow-md">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-marca-dorado text-xl text-marca-azul">
                    <i class="bi bi-trophy-fill" aria-hidden="true"></i>
                </span>

                <div class="min-w-0 flex-1">
                    <p class="font-semibold">{{ $celebracion['titulo'] }}</p>
                    <p class="mt-0.5 break-words text-sm text-marca-gris-claro">{{ $celebracion['mensaje'] }}</p>
                </div>

                <button
                    type="button"
                    x-on:click="abierta = false"
                    aria-label="Cerrar mensaje"
                    class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-marca-gris-claro hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-dorado"
                >
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>
@endif
