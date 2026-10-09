@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-marca-azul focus:ring-marca-azul rounded-md shadow-sm']) }}>
