{{-- Interruptor Lista / Ruta. La preferencia se guarda en sesión (AreaController::vistaElegida). --}}
<nav class="mt-6 flex justify-end" aria-label="Modo de vista">
    <div class="inline-flex rounded-xl border border-marca-azul/20 bg-white p-1 text-sm font-medium shadow-sm">
        @foreach (['lista' => ['Lista', 'bi-list-ul'], 'ruta' => ['Ruta', 'bi-signpost-split']] as $clave => [$etiqueta, $icono])
            <a
                href="{{ route('areas.show', ['area' => $area, 'vista' => $clave]) }}"
                @if ($vista === $clave) aria-current="page" @endif
                class="inline-flex items-center gap-1.5 min-h-10 rounded-lg px-3.5 py-1.5 transition focus-visible:ring-offset-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul {{ $vista === $clave ? 'bg-marca-azul text-white shadow-sm' : 'text-marca-azul hover:bg-marca-azul/5' }}"
            >
                <i class="bi {{ $icono }}" aria-hidden="true"></i>
                {{ $etiqueta }}
            </a>
        @endforeach
    </div>
</nav>
