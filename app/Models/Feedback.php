<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedbacks';
    protected $fillable = ['user_id', 'area_id', 'claridad', 'utilidad', 'ritmo', 'comentario'];
}
