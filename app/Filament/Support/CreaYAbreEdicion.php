<?php

namespace App\Filament\Support;

/**
 * Tras crear un registro que tiene hijos (área, módulo, quiz) se abre su edición,
 * que es donde se agregan.
 */
trait CreaYAbreEdicion
{
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
