<?php

namespace App\Models;

use Database\Factories\IntentoQuizFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntentoQuiz extends Model
{
    /** @use HasFactory<IntentoQuizFactory> */
    use HasFactory;

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = null;

    protected $table = 'intentos_quiz';

    protected $fillable = ['user_id', 'quiz_id', 'puntaje', 'aprobado', 'respuestas'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aprobado' => 'boolean',
            'puntaje' => 'integer',
            'respuestas' => 'array',
            'creado_en' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
