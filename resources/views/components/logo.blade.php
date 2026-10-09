@props(['variante' => 'oscuro'])

{{--
    Logo de Conexa Capital Central. "claro" (texto blanco) va sobre fondos oscuros;
    "oscuro" (texto azul) sobre fondos claros. Los archivos se definen en config/marca.php.
--}}
<img
    src="{{ asset('images/'.rawurlencode(config('marca.logos.'.$variante))) }}"
    alt="{{ config('marca.nombre') }}"
    width="2135"
    height="746"
    {{ $attributes }}
>
