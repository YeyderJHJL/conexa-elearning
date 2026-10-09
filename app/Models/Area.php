<?php

namespace App\Models;

use App\Models\Concerns\TieneImagen;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use TieneImagen;

    /** Paleta de marca de Conexa Capital Central. */
    public const COLOR_MARCA = '#0E1A34';

    public const COLOR_DORADO = '#D7A743';

    public const COLOR_COMPLEMENTARIO = '#4A6FA5';

    protected $fillable = ['nombre', 'slug', 'descripcion', 'icono', 'color', 'imagen', 'resumen_pdf', 'orden', 'activa'];

    /**
     * Color de acento del área: el elegido si es un hex válido (#RRGGBB) o el azul de marca.
     * Se valida porque se imprime dentro de un atributo style.
     *
     * @return Attribute<string, never>
     */
    protected function colorAcento(): Attribute
    {
        return Attribute::get(
            fn (): string => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $this->color) ? strtoupper($this->color) : self::COLOR_MARCA
        );
    }

    public function modulos()
    {
        return $this->hasMany(Modulo::class)->orderBy('orden')->orderBy('id');
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class);
    }
}
