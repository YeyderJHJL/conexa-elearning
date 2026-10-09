<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use App\Services\ProgresoService;
use App\Services\QuizService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuloController extends Controller
{
    /**
     * Detalle de un módulo: sus lecciones activas, cuáles completó el usuario y el estado de su quiz.
     */
    public function show(Request $request, Modulo $modulo, QuizService $quizzes, ProgresoService $progreso): View
    {
        $modulo->load('area');

        $this->authorize('view', $modulo->area);

        abort_unless(
            ($modulo->activo && $modulo->area->activa) || $request->user()->esAdmin(),
            404
        );

        $this->exigirModuloDesbloqueado($request, $modulo);

        $lecciones = $modulo->lecciones()->where('activa', true)->get();

        $completadas = $request->user()
            ->lecciones()
            ->whereIn('leccions.id', $lecciones->modelKeys())
            ->pluck('leccions.id');

        $quiz = $modulo->quiz()->withCount('preguntas')->first();
        $quiz = $quiz?->preguntas_count > 0 ? $quiz : null;

        return view('modulos.show', [
            'modulo' => $modulo,
            'area' => $modulo->area,
            'lecciones' => $lecciones,
            'completadas' => $completadas,
            'leccionesCompletas' => $completadas->count() >= $lecciones->count(),
            'quiz' => $quiz,
            'estadoQuiz' => $quiz ? $quizzes->estadoDe($request->user(), $quiz) : null,
            'notaMinima' => $quiz ? $quizzes->notaMinima($quiz) : null,
            'puedeDescargarResumen' => filled($modulo->resumen_pdf)
                && ($request->user()->esAdmin() || $progreso->moduloCompletado($request->user(), $modulo)),
        ]);
    }
}
