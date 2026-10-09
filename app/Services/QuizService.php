<?php

namespace App\Services;

use App\Models\IntentoQuiz;
use App\Models\Pregunta;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Calificación de quizzes. Todo ocurre en el servidor: las respuestas correctas
 * solo se leen aquí y únicamente se revelan después de que el intento se guardó.
 */
class QuizService
{
    /**
     * Nota mínima (0-100) para aprobar cuando el quiz no define la suya.
     */
    public const NOTA_MINIMA_GLOBAL = 70;

    /**
     * Nota mínima efectiva del quiz.
     */
    public function notaMinima(Quiz $quiz): int
    {
        return $quiz->nota_minima ?? self::NOTA_MINIMA_GLOBAL;
    }

    /**
     * Estado del usuario en un quiz: no_rendido, aprobado (con su mejor intento aprobado)
     * o desaprobado (con su último intento).
     *
     * @return array{estado: 'no_rendido'|'aprobado'|'desaprobado', ultimo: IntentoQuiz|null, aprobado: IntentoQuiz|null, intentos: int}
     */
    public function estadoDe(User $user, Quiz $quiz): array
    {
        $intentos = IntentoQuiz::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->orderByDesc('id')
            ->get();

        $ultimo = $intentos->first();
        $aprobado = $intentos->where('aprobado', true)->sortByDesc('puntaje')->first();

        return [
            'estado' => $aprobado ? 'aprobado' : ($ultimo ? 'desaprobado' : 'no_rendido'),
            'ultimo' => $ultimo,
            'aprobado' => $aprobado,
            'intentos' => $intentos->count(),
        ];
    }

    /**
     * Califica las respuestas y guarda un nuevo intento (los anteriores se conservan).
     *
     * @param  array<int|string, int|string|null>  $respuestas  Opción elegida por id de pregunta.
     */
    public function calificar(User $user, Quiz $quiz, array $respuestas): IntentoQuiz
    {
        $preguntas = $quiz->preguntas()->with('opciones')->get();

        $elegidas = $preguntas->mapWithKeys(fn (Pregunta $pregunta) => [
            $pregunta->id => $pregunta->opciones->firstWhere('id', (int) ($respuestas[$pregunta->id] ?? 0))?->id,
        ]);

        $correctas = $preguntas->filter(
            fn (Pregunta $pregunta) => $pregunta->opciones->firstWhere('id', $elegidas[$pregunta->id])?->es_correcta === true
        )->count();

        $puntaje = $preguntas->isEmpty() ? 0 : intdiv($correctas * 100, $preguntas->count());

        return IntentoQuiz::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'puntaje' => $puntaje,
            'aprobado' => $puntaje >= $this->notaMinima($quiz),
            'respuestas' => $elegidas->all(),
        ]);
    }

    /**
     * Resumen de un intento ya guardado: total de preguntas y las falladas, con la respuesta
     * elegida y la correcta (se puede revelar porque el intento ya fue enviado).
     *
     * @return array{total: int, falladas: Collection<int, array{enunciado: string, elegida: string|null, correcta: string|null}>}
     */
    public function detalle(IntentoQuiz $intento): array
    {
        $respuestas = $intento->respuestas ?? [];
        $preguntas = $intento->quiz->preguntas()->with('opciones')->get();

        $falladas = $preguntas
            ->map(function (Pregunta $pregunta) use ($respuestas) {
                $elegida = $pregunta->opciones->firstWhere('id', $respuestas[$pregunta->id] ?? null);

                if ($elegida?->es_correcta) {
                    return null;
                }

                return [
                    'enunciado' => $pregunta->enunciado,
                    'elegida' => $elegida?->texto,
                    'correcta' => $pregunta->opciones->firstWhere('es_correcta', true)?->texto,
                ];
            })
            ->filter()
            ->values();

        return ['total' => $preguntas->count(), 'falladas' => $falladas];
    }
}
