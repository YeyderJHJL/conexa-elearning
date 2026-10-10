<?php

namespace App\Filament\Support;

/**
 * Aplica la regla de {@see VideoUnico} al crear y guardar una lección desde su propia página.
 */
trait GuardaVideoUnico
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return VideoUnico::normalizar($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = VideoUnico::normalizar($data);

        VideoUnico::borrarAnteriorSiCambio($this->getRecord()->archivo_video, $data['archivo_video'] ?? null);

        return $data;
    }
}
