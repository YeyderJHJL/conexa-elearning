<?php

namespace App\Filament\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * Una lección tiene como máximo UNA fuente de video: un archivo subido o un enlace.
 * El formulario envía tipo_video ('ninguno' | 'archivo' | 'enlace'); aquí se limpia la fuente descartada
 * y se borra del disco el archivo que dejó de usarse.
 */
class VideoUnico
{
    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    public static function normalizar(array $datos): array
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

    public static function borrarAnteriorSiCambio(?string $anterior, ?string $nuevo): void
    {
        if (filled($anterior) && $anterior !== $nuevo) {
            Storage::disk(config('filament.default_filesystem_disk'))->delete($anterior);
        }
    }
}
