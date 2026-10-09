@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-sm text-marca-azul']) }}>
    {{ $value ?? $slot }}
</label>
