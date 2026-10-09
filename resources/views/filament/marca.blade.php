@props(['variante' => 'oscuro'])

{{--
    Marca del panel: logo de Conexa + identificador de la plataforma ("CONEXA | E-learning"),
    con "E-learning" en dorado y sin fondo. Variante "oscuro" = logo para fondos claros;
    variante "claro" = logo para fondos oscuros (modo oscuro).
--}}
@php
    $archivo = config('marca.logos.'.($variante === 'claro' ? 'claro' : 'oscuro'));
    $sobreOscuro = $variante === 'claro';
@endphp

<span class="flex items-center gap-3" data-marca-plataforma>
    <img src="{{ asset('images/'.rawurlencode($archivo)) }}" alt="{{ config('marca.nombre') }}" style="height: 2.5rem; width: auto;">

    <span class="h-6 w-px {{ $sobreOscuro ? 'bg-white/25' : 'bg-gray-300' }}" aria-hidden="true"></span>

    <span class="text-sm font-semibold tracking-wide" style="color: {{ config('marca.colores.dorado') }}">{{ config('marca.plataforma') }}</span>
</span>
