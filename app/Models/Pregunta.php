<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pregunta extends Model
{
    protected $fillable = ['quiz_id', 'enunciado', 'orden'];
    public function quiz() 
    { 
        return $this->belongsTo(Quiz::class); 
    }
    public function opciones() 
    { 
        return $this->hasMany(Opcion::class); 
    }
}
