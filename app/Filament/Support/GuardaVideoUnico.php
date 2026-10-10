<?php

namespace App\Filament\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * Una lección tiene como máximo UNA fuente de video: un archivo subido o un enlace.
 * El formulario envía tipo_video ('ninguno' | 'archivo' | 'enlace'); aquí se limpia la fuente descartada
 * y se borra del disco el archivo que dejó de usarse.
 */
trait GuardaVideoUnico
{
    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    protected function dejarUnSoloVideo(array $datos): array
    {
        $tipo = Arr::pull($datos, 'tipo_video', 'ninguno');

        if ($tipo !== 'archivo') {
            $datos['archivo_video'] = null;
        }

        if ($tipo !== 'enlace') {
            $datos['url_video'] = null;
        }

        return $datos;
    }

    protected function borrarVideoAnteriorSiCambio(?string $anterior, ?string $nuevo): void
    {
        if (filled($anterior) && $anterior !== $nuevo) {
            Storage::disk(config('filament.default_filesystem_disk'))->delete($anterior);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->dejarUnSoloVideo($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->dejarUnSoloVideo($data);

        $this->borrarVideoAnteriorSiCambio($this->getRecord()->archivo_video, $data['archivo_video'] ?? null);

        return $data;
    }
}
