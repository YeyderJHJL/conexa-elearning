<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuloAprobado extends Model
{
    protected $fillable = ['user_id', 'modulo_id', 'nota', 'aprobado_en'];
    protected $casts = ['aprobado_en' => 'datetime'];
}
