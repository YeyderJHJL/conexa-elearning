{{--
    Título de la pestaña del panel: "Conexa E-learning — <sección>".
    Filament arma el <title> como "<sección> - <marca>" en su vista base; aquí solo se reordena en el navegador
    (y se reaplica si Livewire cambia el título al navegar). Es apariencia: no altera rutas ni datos.
--}}
<script data-titulo-panel>
    (() => {
        const marca = @js(config('marca.nombre_corto').' '.config('marca.plataforma'));
        const sufijo = ' - ' + marca;
        const etiqueta = document.querySelector('title');

        if (! etiqueta) {
            return;
        }

        const reordenar = () => {
            const actual = etiqueta.textContent.replace(/\s+/g, ' ').trim();

            if (! actual.endsWith(sufijo)) {
                return;
            }

            const nuevo = marca + ' — ' + actual.slice(0, -sufijo.length);

            if (document.title !== nuevo) {
                document.title = nuevo;
            }
        };

        reordenar();
        new MutationObserver(reordenar).observe(etiqueta, { childList: true, characterData: true, subtree: true });
    })();
</script>
