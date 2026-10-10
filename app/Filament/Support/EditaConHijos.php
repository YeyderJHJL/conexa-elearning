<?php

namespace App\Filament\Support;

/**
 * Patrón de las páginas de edición de un registro con hijos (área, módulo, quiz): una pestaña con
 * sus datos y una pestaña por cada gestor de hijos (tabla + modal).
 */
trait EditaConHijos
{
    abstract protected function etiquetaDeDatos(): string;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return $this->etiquetaDeDatos();
    }
}
