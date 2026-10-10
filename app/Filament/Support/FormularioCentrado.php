<?php

namespace App\Filament\Support;

use Filament\Support\Enums\Width;

/**
 * Páginas de crear y editar: el formulario ocupa un solo carril, con el mismo ancho en todo el panel
 * y centrado, en lugar de estirarse a todo el ancho de la pantalla.
 */
trait FormularioCentrado
{
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FiveExtraLarge;
    }
}
