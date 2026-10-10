@props(['disabled' => false])

{{-- Campo de texto. Si el campo tiene error de validación (en cualquier "bolsa" de errores) se marca en rojo. --}}
@php
    $nombre = $attributes->get('name');
    $invalido = filled($nombre) && (
        $errors->has($nombre)
        || $errors->updatePassword->has($nombre)
        || $errors->userDeletion->has($nombre)
    );
@endphp

<input
    @disabled($disabled)
    @if ($invalido) aria-invalid="true" @endif
    {{ $attributes->merge(['class' => 'rounded-xl border-gray-300 bg-white py-2.5 shadow-sm placeholder:text-marca-gris-texto transition focus:border-marca-azul focus:ring-2 focus:ring-marca-azul/25 disabled:bg-gray-100 aria-[invalid=true]:border-red-400 aria-[invalid=true]:focus:border-red-500 aria-[invalid=true]:focus:ring-red-200']) }}
>
