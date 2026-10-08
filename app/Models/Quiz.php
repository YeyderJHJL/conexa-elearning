<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = ['modulo_id', 'titulo', 'nota_minima'];

    public function modulo()
    {
        return $this->belongsTo(Modulo::class);
    }

    public function preguntas()
    {
        return $this->hasMany(Pregunta::class)->orderBy('orden')->orderBy('id');
    }
}
