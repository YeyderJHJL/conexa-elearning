<nav aria-label="Principal" class="relative z-30 border-b-2 border-marca-dorado bg-marca-azul">
    {{-- Arco dorado del logo como motivo decorativo sutil. --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <svg class="pointer-events-none absolute -right-10 top-0 h-full w-[34rem] max-w-none opacity-20" viewBox="0 0 540 72" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0 72 C 190 68 390 40 540 0 C 400 38 200 62 0 72 Z" fill="{{ config('marca.colores.dorado') }}" />
        </svg>
    </div>

    <!-- Primary Navigation Menu -->
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-[4.5rem] items-center justify-between">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-dorado">
                        <x-logo variante="claro" class="block h-10 w-auto" />
                    </a>

                    {{-- Identificador de la plataforma: "CONEXA · E-learning" --}}
                    <span class="ms-3 flex items-center gap-3" data-plataforma>
                        <span class="h-6 w-px bg-white/25" aria-hidden="true"></span>
                        <span class="text-xs font-semibold tracking-wide text-marca-dorado sm:text-sm">{{ config('marca.plataforma') }}</span>
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-1 sm:gap-2">
                <!-- Nombre y rol, al costado del botón de cuenta -->
                <div class="hidden items-center gap-3 md:flex" data-nombre-usuario>
                    <div class="text-end leading-tight">
                        <p class="max-w-[12rem] truncate text-sm font-semibold text-white">{{ Auth::user()->name }}</p>
                        @if (filled(Auth::user()->cargo))
                            <p class="max-w-[12rem] truncate text-xs text-marca-gris-claro">{{ Auth::user()->cargo }}</p>
                        @endif
                    </div>

                    <span class="insignia bg-marca-dorado text-marca-azul" data-rol-usuario>
                        <i class="bi bi-person-badge-fill" aria-hidden="true"></i>
                        {{ Auth::user()->etiquetaRol() }}
                    </span>
                </div>

                <!-- Settings Dropdown -->
                <x-dropdown align="right" width="w-72">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-1.5 rounded-xl px-2 py-1.5 text-marca-gris-claro transition duration-150 hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-dorado motion-reduce:transition-none" aria-label="Menú de cuenta">
                            <x-encabezado-usuario />

                            <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-menu-cuenta />
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>
