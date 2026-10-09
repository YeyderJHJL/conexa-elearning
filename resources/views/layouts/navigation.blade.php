<nav x-data="{ open: false }" aria-label="Principal" class="relative z-30 border-b-2 border-marca-dorado bg-marca-azul">
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
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
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
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-1.5 rounded-xl px-2 py-1.5 text-marca-gris-claro transition duration-150 hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-dorado motion-reduce:transition-none" aria-label="Menú de cuenta">
                            <x-encabezado-usuario />

                            <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>

                <!-- Hamburger -->
                <div class="flex items-center sm:hidden">
                    <button @click="open = ! open" :aria-expanded="open.toString()" class="inline-flex items-center justify-center rounded-md p-2 text-marca-gris-claro transition duration-150 ease-in-out hover:bg-marca-profundo hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-dorado" aria-label="Menú">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="relative hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-marca-profundo">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-marca-gris-claro">{{ Auth::user()->email }}</div>
                <span class="insignia mt-2 bg-marca-dorado text-marca-azul">
                    <i class="bi bi-person-badge-fill" aria-hidden="true"></i>
                    {{ Auth::user()->etiquetaRol() }}
                </span>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
