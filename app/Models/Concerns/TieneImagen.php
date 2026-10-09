<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Imagen de portada guardada en el disco público (storage/app/public, servido por public/storage).
 *
 * @property string|null $imagen
 */
trait TieneImagen
{
    /**
     * URL pública de la imagen, o null si no tiene. No comprueba que el archivo exista.
     *
     * @return Attribute<string|null, never>
     */
    protected function imagenUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => filled($this->imagen) ? asset('storage/'.ltrim($this->imagen, '/')) : null);
    }
}
