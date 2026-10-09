@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-marca-azul focus:ring-marca-dorado rounded-md shadow-sm']) }}>
