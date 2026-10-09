<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos del reporte de capacitación de un trabajador. Todo sale de la base de datos:
 * el avance y los estados, de ProgresoService; las notas, de los intentos de quiz guardados.
 */
class ReporteService
{
    public function __construct(
        private readonly ProgresoService $progreso,
        private readonly QuizService $quizzes,
    ) {}

    /**
     * @return array{
     *     usuario: User,
     *     generadoEn: Carbon,
     *     global: int,
     *     estadoFinal: string,
     *     areas: Collection<int, array{area: Area, avance: int, modulos: Collection<int, array<string, mixed>>}>,
     *     reforzar: Collection<int, array<string, mixed>>,
     *     leccionesNuevas: Collection<int, array<string, mixed>>
     * }
     */
    public function datosPara(User $user): array
    {
        $areas = $user->areas()
            ->where('activa', true)
            ->orderBy('orden')
            ->orderBy('id')
            ->with(['modulos' => fn ($query) => $query
                ->where('activo', true)
                ->with(['quiz' => fn ($quiz) => $quiz->withCount('preguntas')])
                ->withCount(['lecciones as lecciones_activas_count' => fn ($lecciones) => $lecciones->where('activa', true)]),
            ])
            ->get();

        $resumen = $this->progreso->resumen($user, $areas);
        $estados = $this->progreso->estadosDe($user, $areas);
        $intentos = $this->intentosPorQuiz($user, $areas);

        $filasAreas = $areas->map(fn (Area $area) => [
            'area' => $area,
            'avance' => $resumen['areas'][$area->id],
            'modulos' => $area->modulos->map(fn (Modulo $modulo) => $this->filaModulo(
                $modulo,
                $estados->get($area->id)?->get($modulo->id) ?? ProgresoService::BLOQUEADO,
                $intentos
            ))->values(),
        ])->values();

        return [
            'usuario' => $user,
            'generadoEn' => now(),
            'global' => $resumen['global'],
            'estadoFinal' => $resumen['global'] >= 100 ? 'Completado' : 'En progreso',
            'areas' => $filasAreas,
            'reforzar' => $this->puntosAReforzar($filasAreas),
            'leccionesNuevas' => $this->leccionesNuevas($user, $filasAreas, $intentos),
        ];
    }

    /**
     * Resumen de intentos del usuario por quiz: total, mejor nota, si aprobó y fecha del primer intento aprobado.
     *
     * @param  Collection<int, Area>  $areas
     * @return Collection<int, object>
     */
    private function intentosPorQuiz(User $user, Collection $areas): Collection
    {
        $quizIds = $areas->flatMap(fn (Area $area) => $area->modulos)
            ->map(fn (Modulo $modulo) => $this->quizConPreguntas($modulo)?->id)
            ->filter()
            ->values()
            ->all();

        return DB::table('intentos_quiz')
            ->where('user_id', $user->id)
            ->whereIn('quiz_id', $quizIds)
            ->groupBy('quiz_id')
            ->selectRaw('quiz_id, count(*) as total, max(puntaje) as mejor, max(aprobado) as aprobado, min(case when aprobado = 1 then creado_en end) as aprobado_en')
            ->get()
            ->keyBy('quiz_id');
    }

    /**
     * @param  Collection<int, object>  $intentos
     * @return array<string, mixed>
     */
    private function filaModulo(Modulo $modulo, string $estado, Collection $intentos): array
    {
        $quiz = $this->quizConPreguntas($modulo);
        $resumen = $quiz ? $intentos->get($quiz->id) : null;

        return [
            'modulo' => $modulo,
            'estado' => $estado,
            'tieneQuiz' => $quiz !== null,
            'intentos' => (int) ($resumen->total ?? 0),
            'mejorNota' => $resumen ? (int) $resumen->mejor : null,
            'aprobado' => (bool) ($resumen->aprobado ?? false),
            'notaMinima' => $quiz ? $this->quizzes->notaMinima($quiz) : null,
            'quizId' => $quiz?->id,
        ];
    }

    /**
     * Módulos con quiz rendido que el trabajador todavía no aprobó, de menor a mayor mejor nota.
     *
     * @param  Collection<int, array<string, mixed>>  $areas
     * @return Collection<int, array<string, mixed>>
     */
    private function puntosAReforzar(Collection $areas): Collection
    {
        return $areas
            ->flatMap(fn (array $fila) => $fila['modulos']->map(fn (array $modulo) => $modulo + ['area' => $fila['area']]))
            ->filter(fn (array $modulo) => $modulo['tieneQuiz'] && $modulo['intentos'] > 0 && ! $modulo['aprobado'])
            ->sortBy('mejorNota')
            ->values();
    }

    /**
     * Módulos con quiz aprobado a los que se agregaron lecciones activas después de esa aprobación.
     *
     * @param  Collection<int, array<string, mixed>>  $areas
     * @param  Collection<int, object>  $intentos
     * @return Collection<int, array<string, mixed>>
     */
    private function leccionesNuevas(User $user, Collection $areas, Collection $intentos): Collection
    {
        $aprobados = $areas
            ->flatMap(fn (array $fila) => $fila['modulos']->map(fn (array $modulo) => $modulo + ['area' => $fila['area']]))
            ->filter(fn (array $modulo) => $modulo['aprobado'] && $intentos->get($modulo['quizId'])?->aprobado_en !== null)
            ->values();

        if ($aprobados->isEmpty()) {
            return collect();
        }

        $lecciones = Leccion::query()
            ->whereIn('modulo_id', $aprobados->map(fn (array $modulo) => $modulo['modulo']->id)->all())
            ->where('activa', true)
            ->get(['id', 'modulo_id', 'created_at'])
            ->groupBy('modulo_id');

        $vistas = $user->lecciones()->pluck('leccions.id')->flip();

        return $aprobados
            ->map(function (array $modulo) use ($intentos, $lecciones, $vistas) {
                $aprobadoEn = Carbon::parse($intentos->get($modulo['quizId'])->aprobado_en);
                $nuevas = $lecciones->get($modulo['modulo']->id, collect())
                    ->filter(fn (Leccion $leccion) => $leccion->created_at->gt($aprobadoEn));

                return [
                    'area' => $modulo['area'],
                    'modulo' => $modulo['modulo'],
                    'aprobadoEn' => $aprobadoEn,
                    'nuevas' => $nuevas->count(),
                    'pendientes' => $nuevas->reject(fn (Leccion $leccion) => $vistas->has($leccion->id))->count(),
                ];
            })
            ->filter(fn (array $nota) => $nota['nuevas'] > 0)
            ->values();
    }

    private function quizConPreguntas(Modulo $modulo): ?Quiz
    {
        return $modulo->quiz && $modulo->quiz->preguntas_count > 0 ? $modulo->quiz : null;
    }
}
