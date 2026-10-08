<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = ['nombre', 'slug', 'descripcion', 'icono', 'orden', 'activa'];

    public function modulos()
    {
        return $this->hasMany(Modulo::class)->orderBy('orden')->orderBy('id');
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class);
    }
}
