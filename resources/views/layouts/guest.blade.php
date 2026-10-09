<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="{{ config('marca.colores.azul') }}">

        <title>{{ config('marca.nombre_corto') }} {{ config('marca.plataforma') }}@if (filled($seccion = $attributes->get('titulo'))) — {{ $seccion }}@endif</title>

        <!-- Favicon -->
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        <!-- Fondo claro/oscuro elegido por la persona (se aplica antes de pintar para evitar parpadeos) -->
        <script>
            try { if (localStorage.getItem('tema') === 'oscuro') { document.documentElement.classList.add('dark'); } } catch (e) {}
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-marca-azul px-4 py-10">
            {{-- Arco dorado del logo como motivo decorativo. --}}
            <svg class="pointer-events-none absolute -bottom-24 -right-32 h-[26rem] w-[44rem] max-w-none opacity-[0.16]" viewBox="0 0 540 330" aria-hidden="true">
                <path d="M0 330 C 140 300 380 180 540 0 C 420 150 190 290 0 330 Z" fill="{{ config('marca.colores.dorado') }}" />
            </svg>
            <svg class="pointer-events-none absolute -left-24 -top-10 h-[20rem] w-[34rem] max-w-none opacity-[0.07]" viewBox="0 0 540 330" aria-hidden="true">
                <path d="M0 330 C 140 300 380 180 540 0 C 420 150 190 290 0 330 Z" fill="#ffffff" />
            </svg>

            <a href="/" class="relative rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-dorado">
                <x-logo variante="claro" class="h-auto w-56 sm:w-64" />
            </a>

            <p class="relative mt-2 text-sm font-semibold tracking-[0.3em] text-marca-dorado" data-plataforma>{{ strtoupper(config('marca.plataforma')) }}</p>

            <div class="relative mt-8 w-full overflow-hidden rounded-3xl border-t-4 border-marca-dorado bg-white px-6 py-7 shadow-tarjeta-hover sm:max-w-md sm:px-8 sm:py-8">
                {{ $slot }}
            </div>

            <p class="relative mt-6 text-center text-xs text-marca-gris-claro">Plataforma de capacitación · {{ config('marca.nombre') }}</p>
        </div>
    </body>
</html>
