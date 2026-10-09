{{-- Menú del botón de usuario (como el del panel de administración): fondo claro/oscuro, perfil y cerrar sesión. --}}
@php
    $item = 'flex w-full items-center gap-3 px-4 py-2 text-start text-sm text-gray-700 transition duration-150 hover:bg-marca-azul/5 hover:text-marca-azul focus:bg-marca-azul/5 focus:outline-none';
    $opcion = 'inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul motion-reduce:transition-none';
@endphp

<div data-menu-cuenta>
    {{-- En pantallas pequeñas la barra no muestra el nombre: se ve aquí. --}}
    <div class="border-b border-gray-100 px-4 py-3 md:hidden">
        <p class="truncate text-sm font-semibold text-marca-azul">{{ $usuario->name }}</p>
        <p class="truncate text-xs text-marca-gris-texto">{{ $usuario->email }}</p>
        <span class="insignia mt-2 bg-marca-dorado text-marca-azul">
            <i class="bi bi-person-badge-fill" aria-hidden="true"></i> {{ $usuario->etiquetaRol() }}
        </span>
    </div>

    {{-- Cambiar el fondo: la elección se guarda en este navegador. --}}
    <div
        class="px-4 pb-2 pt-3"
        data-selector-fondo
        role="group"
        aria-label="Fondo de la página"
        x-data="{
            oscuro: document.documentElement.classList.contains('dark'),
            poner(valor) {
                this.oscuro = valor;
                document.documentElement.classList.toggle('dark', valor);
                try { localStorage.setItem('tema', valor ? 'oscuro' : 'claro'); } catch (e) {}
            },
        }"
        @click.stop
    >
        <p class="pb-1.5 text-[0.7rem] font-bold uppercase tracking-[0.09em] text-marca-gris-texto">Fondo</p>
        <div class="flex gap-1 rounded-xl bg-marca-azul/5 p-1">
            <button type="button" @click="poner(false)" :aria-pressed="(! oscuro).toString()" :class="oscuro ? 'text-marca-gris-texto hover:text-marca-azul' : 'bg-white text-marca-azul shadow-sm'" class="{{ $opcion }}">
                <i class="bi bi-sun" aria-hidden="true"></i> Claro
            </button>
            <button type="button" @click="poner(true)" :aria-pressed="oscuro.toString()" :class="oscuro ? 'bg-white text-marca-azul shadow-sm' : 'text-marca-gris-texto hover:text-marca-azul'" class="{{ $opcion }}">
                <i class="bi bi-moon-stars" aria-hidden="true"></i> Oscuro
            </button>
        </div>
    </div>

    <a href="{{ route('profile.edit') }}" class="{{ $item }}">
        <i class="bi bi-person-circle text-base text-marca-gris-texto" aria-hidden="true"></i> {{ __('Profile') }}
    </a>

    <form method="POST" action="{{ route('logout') }}" class="pb-1">
        @csrf
        <button type="submit" class="{{ $item }}">
            <i class="bi bi-box-arrow-right text-base text-marca-gris-texto" aria-hidden="true"></i> {{ __('Log Out') }}
        </button>
    </form>
</div>
