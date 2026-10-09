{{--
    Barra superior del panel: nombre del usuario y su rol (se coloca antes del menú de cuenta).
    Se oculta en pantallas pequeñas para no apretar la barra; el menú de cuenta sigue disponible.
--}}
@php($usuario = auth()->user())

@if ($usuario)
    <div class="hidden items-center gap-2.5 sm:flex" data-topbar-usuario>
        <div class="text-end leading-tight">
            <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $usuario->name }}</p>
            @if (filled($usuario->cargo))
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $usuario->cargo }}</p>
            @endif
        </div>

        <x-filament::badge :color="$usuario->esAdmin() ? 'secondary' : 'primary'" icon="heroicon-m-shield-check" size="sm">
            {{ $usuario->etiquetaRol() }}
        </x-filament::badge>
    </div>
@endif
