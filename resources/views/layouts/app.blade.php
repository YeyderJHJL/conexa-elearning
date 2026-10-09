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
        @stack('styles')
    </head>
    <body class="font-sans antialiased">
        <a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:font-semibold focus:text-marca-azul focus:shadow-lg">
            Saltar al contenido
        </a>

        <div class="min-h-screen bg-marca-fondo">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="border-b border-marca-azul/10 bg-white">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main id="contenido" tabindex="-1" class="focus:outline-none">
                <x-celebracion />

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
