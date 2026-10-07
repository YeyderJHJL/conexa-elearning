<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opcion extends Model
{
    protected $fillable = ['pregunta_id', 'texto', 'es_correcta'];
    protected $casts = ['es_correcta' => 'boolean'];
    public function pregunta() 
    { 
        return $this->belongsTo(Pregunta::class); 
    }
}
