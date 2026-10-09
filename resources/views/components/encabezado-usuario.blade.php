{{-- Avance global (anillo) y avatar con iniciales. Va dentro del botón del menú de cuenta; el nombre y el rol se muestran al costado. --}}
<span class="flex items-center gap-2.5" data-encabezado-usuario>
    <x-anillo-progreso :valor="$avance" :tamano="44" :grosor="4" etiqueta="Avance global" class="text-white" />

    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-marca-dorado text-sm font-semibold text-marca-azul shadow-sm ring-2 ring-white/20" aria-hidden="true">{{ $iniciales }}</span>
</span>
