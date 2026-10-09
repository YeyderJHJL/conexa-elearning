<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    protected $fillable = ['area_id', 'titulo', 'descripcion', 'resumen_pdf', 'orden', 'activo'];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function lecciones()
    {
        return $this->hasMany(Leccion::class)->orderBy('orden')->orderBy('id');
    }

    public function quiz()
    {
        return $this->hasOne(Quiz::class);
    }
}
