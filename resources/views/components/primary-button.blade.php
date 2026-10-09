<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2.5 bg-marca-azul border border-transparent rounded-xl font-semibold text-sm text-white shadow-sm hover:bg-marca-profundo focus:bg-marca-profundo active:bg-marca-azul focus:outline-none focus:ring-2 focus:ring-marca-azul focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
