<x-app-layout titulo="Mi perfil">
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endpush

    @php
        $usuario = $user ?? auth()->user();
        $iniciales = \App\View\Components\EncabezadoUsuario::calcularIniciales($usuario->name);
    @endphp

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            {{-- Cabecera del perfil --}}
            <header class="aparece relative overflow-hidden rounded-3xl bg-marca-azul p-6 text-white shadow-tarjeta-hover sm:p-8">
                <svg class="pointer-events-none absolute -bottom-10 -right-16 h-72 w-[30rem] max-w-none opacity-[0.14]" viewBox="0 0 540 330" aria-hidden="true">
                    <path d="M0 330 C 140 300 380 180 540 0 C 420 150 190 290 0 330 Z" fill="{{ config('marca.colores.dorado') }}" />
                </svg>

                <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center">
                    <span class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-marca-dorado text-3xl font-semibold text-marca-azul shadow-nodo ring-4 ring-white/20" aria-hidden="true">{{ $iniciales }}</span>

                    <div class="min-w-0">
                        <p class="text-sm font-medium text-marca-gris-claro">Mi perfil</p>
                        <h1 class="break-words text-2xl font-semibold tracking-tight sm:text-3xl">{{ $usuario->name }}</h1>
                        <p class="break-all text-sm text-marca-gris-claro">{{ $usuario->email }}</p>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="insignia bg-marca-dorado text-marca-azul" data-rol-usuario>
                                <i class="bi bi-person-badge-fill" aria-hidden="true"></i>
                                {{ $usuario->etiquetaRol() }}
                            </span>

                            @if (filled($usuario->cargo))
                                <span class="insignia bg-white/10 text-white">
                                    <i class="bi bi-briefcase" aria-hidden="true"></i> {{ $usuario->cargo }}
                                </span>
                            @endif

                            @unless ($usuario->esAdmin())
                                <span class="insignia bg-white/10 text-white">
                                    <i class="bi bi-collection" aria-hidden="true"></i>
                                    {{ trans_choice(':count área asignada|:count áreas asignadas', $usuario->areas->count()) }}
                                </span>
                            @endunless
                        </div>
                    </div>
                </div>
            </header>

            <section class="tarjeta aparece p-5 sm:p-8" style="animation-delay: 60ms">
                <div class="flex items-start gap-4">
                    <span class="hidden h-12 w-12 shrink-0 place-items-center rounded-2xl bg-marca-azul/5 text-2xl text-marca-azul sm:grid">
                        <i class="bi bi-person-vcard" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0 flex-1 sm:max-w-xl">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </section>

            <section class="tarjeta aparece p-5 sm:p-8" style="animation-delay: 120ms">
                <div class="flex items-start gap-4">
                    <span class="hidden h-12 w-12 shrink-0 place-items-center rounded-2xl bg-marca-azul/5 text-2xl text-marca-azul sm:grid">
                        <i class="bi bi-shield-lock" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0 flex-1 sm:max-w-xl">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            </section>

            <section class="tarjeta aparece border-red-200 bg-red-50/40 p-5 sm:p-8" style="animation-delay: 180ms">
                <div class="flex items-start gap-4">
                    <span class="hidden h-12 w-12 shrink-0 place-items-center rounded-2xl bg-red-100 text-2xl text-red-600 sm:grid">
                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0 flex-1 sm:max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
