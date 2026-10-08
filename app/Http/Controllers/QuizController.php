<?php

namespace App\Http\Controllers;

use App\Models\IntentoQuiz;
use App\Models\Modulo;
use App\Models\Quiz;
use App\Services\ProgresoService;
use App\Services\QuizService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function __construct(
        private readonly ProgresoService $progreso,
        private readonly QuizService $quizzes,
    ) {}

    /**
     * Formulario del quiz. Las opciones se cargan sin `es_correcta`: la respuesta correcta nunca viaja al navegador.
     */
    public function show(Request $request, Modulo $modulo): View
    {
        $quiz = $this->quizAccesible($request, $modulo);

        $quiz->load(['preguntas.opciones' => fn ($query) => $query->select('id', 'pregunta_id', 'texto')]);

        return view('quiz.show', [
            'modulo' => $modulo,
            'quiz' => $quiz,
            'notaMinima' => $this->quizzes->notaMinima($quiz),
        ]);
    }

    /**
     * Califica en el servidor, guarda el intento y muestra su resultado.
     */
    public function store(Request $request, Modulo $modulo): RedirectResponse
    {
        $quiz = $this->quizAccesible($request, $modulo);

        $reglas = $quiz->preguntas()->pluck('id')
            ->mapWithKeys(fn (int $id) => ["respuestas.{$id}" => ['required', 'integer']])
            ->all();

        $datos = $request->validate($reglas, [
            'respuestas.*.required' => 'Responde todas las preguntas.',
            'respuestas.*.integer' => 'Respuesta no válida.',
        ]);

        $intento = $this->quizzes->calificar($request->user(), $quiz, $datos['respuestas'] ?? []);

        return redirect()->route('quiz.resultado', [$modulo, $intento]);
    }

    /**
     * Resultado de un intento propio: puntaje, aprobado y preguntas falladas con su respuesta correcta.
     */
    public function resultado(Request $request, Modulo $modulo, IntentoQuiz $intento): View
    {
        $modulo->load('area');

        $this->authorize('view', $modulo->area);

        abort_unless(
            $intento->user_id === $request->user()->id && $intento->quiz_id === $modulo->quiz?->id,
            404
        );

        $intento->load('quiz');

        return view('quiz.resultado', [
            'modulo' => $modulo,
            'intento' => $intento,
            'notaMinima' => $this->quizzes->notaMinima($intento->quiz),
            'detalle' => $this->quizzes->detalle($intento),
        ]);
    }

    /**
     * Valida que el usuario pueda rendir el quiz del módulo y lo devuelve.
     */
    private function quizAccesible(Request $request, Modulo $modulo): Quiz
    {
        $modulo->load('area');

        $this->authorize('view', $modulo->area);

        abort_unless(($modulo->activo && $modulo->area->activa) || $request->user()->esAdmin(), 404);

        $this->exigirModuloDesbloqueado($request, $modulo);

        $quiz = $modulo->quiz;

        if (! $quiz || ! $quiz->preguntas()->exists()) {
            $this->volverAlModulo($modulo, 'Este módulo aún no tiene un quiz disponible.');
        }

        if (! $request->user()->esAdmin() && ! $this->progreso->leccionesCompletadas($request->user(), $modulo)) {
            $this->volverAlModulo($modulo, 'Completa todas las lecciones del módulo antes de rendir el quiz.');
        }

        return $quiz;
    }

    private function volverAlModulo(Modulo $modulo, string $aviso): never
    {
        throw new HttpResponseException(redirect()->route('modulos.show', $modulo)->with('aviso', $aviso));
    }
}
