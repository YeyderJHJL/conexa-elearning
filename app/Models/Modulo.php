<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    protected $fillable = ['area_id', 'titulo', 'descripcion', 'orden', 'activo'];
    
    public function area() 
    { 
        return $this->belongsTo(Area::class); 
    }
    
    public function lecciones() 
    { 
        return $this->hasMany(Leccion::class)->orderBy('orden'); 
    }
    
    public function quiz() 
    { 
        return $this->hasOne(Quiz::class); 
    }
}
