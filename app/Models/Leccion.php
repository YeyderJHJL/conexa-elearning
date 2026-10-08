<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leccion extends Model
{
    protected $fillable = ['modulo_id', 'titulo', 'contenido', 'url_video', 'archivo_pdf', 'duracion_min', 'orden', 'activa'];

    public function modulo()
    {
        return $this->belongsTo(Modulo::class);
    }
}
