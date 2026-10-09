<?php

namespace App\Services;

use App\Models\Area;
use App\Models\IntentoQuiz;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Camino de un área (modo "ruta") para un usuario: un nodo por lección, un nodo de quiz al final
 * de cada módulo con quiz, y un nodo final de encuesta cuando corresponde.
 *
 * No define reglas propias de progreso: el estado de cada módulo (completado, en curso o bloqueado
 * por la secuencia) sale de ProgresoService; aquí solo se leen las lecciones vistas y los quizzes
 * aprobados para decidir el estado de cada nodo.
 *
 * Estados de nodo:
 *  - completado: lección vista, quiz aprobado o encuesta respondida.
 *  - actual: el primer paso pendiente de todo el camino (como máximo uno).
 *  - disponible: lección pendiente de un módulo abierto que viene después de la actual
 *    (la app no obliga a ver las lecciones de un módulo en orden).
 *  - bloqueado: cualquier nodo de un módulo bloqueado por la secuencia, y el quiz mientras falten lecciones.
 */
class RutaService
{
    public const COMPLETADO = 'completado';

    public const ACTUAL = 'actual';

    public const DISPONIBLE = 'disponible';

    public const BLOQUEADO = 'bloqueado';

    public function __construct(private readonly ProgresoService $progreso) {}

    /**
     * @param  bool  $accesoTotal  Los administradores pueden abrir cualquier nodo aunque se vea bloqueado.
     * @param  'pendiente'|'respondida'|null  $estadoEncuesta
     * @return array{
     *     modulos: Collection<int, array{modulo: Modulo, estado: string, nodos: Collection<int, array<string, mixed>>, lecciones: int, vistas: int}>,
     *     encuesta: array<string, mixed>|null,
     *     actual: array<string, mixed>|null
     * }
     */
    public function paraArea(User $usuario, Area $area, bool $accesoTotal = false, ?string $estadoEncuesta = null): array
    {
        $modulos = $area->modulos()
            ->where('activo', true)
            ->with([
                'lecciones' => fn ($query) => $query->where('activa', true),
                'quiz' => fn ($query) => $query->withCount('preguntas'),
            ])
            ->get();

        $estados = $this->progreso->estados($usuario, $area);
        $vistas = $usuario->lecciones()->pluck('leccions.id')->flip();
        $aprobados = $this->quizzesAprobados($usuario, $modulos);

        $hayActual = false;
        $actual = null;

        $tramos = $modulos->map(function (Modulo $modulo) use ($estados, $vistas, $aprobados, $accesoTotal, &$hayActual, &$actual) {
            $estadoModulo = $estados->get($modulo->id, ProgresoService::BLOQUEADO);
            $abierto = $estadoModulo !== ProgresoService::BLOQUEADO;
            $nodos = collect();

            foreach ($modulo->lecciones as $indice => $leccion) {
                $estado = match (true) {
                    ! $abierto => self::BLOQUEADO,
                    $vistas->has($leccion->id) => self::COMPLETADO,
                    default => $this->siguienteEstado($hayActual),
                };

                $nodos->push($this->nodo('leccion', $leccion->id, $leccion->titulo, 'Lección '.($indice + 1), $estado, route('lecciones.show', $leccion), $accesoTotal));
            }

            $quiz = $modulo->quiz;

            if ($quiz && $quiz->preguntas_count > 0) {
                $leccionesVistas = $modulo->lecciones->every(fn (Leccion $leccion) => $vistas->has($leccion->id));

                $estado = match (true) {
                    ! $abierto => self::BLOQUEADO,
                    $aprobados->has($quiz->id) => self::COMPLETADO,
                    $leccionesVistas => $this->siguienteEstado($hayActual),
                    default => self::BLOQUEADO,
                };

                $nodos->push($this->nodo('quiz', $quiz->id, $quiz->titulo, 'Quiz del módulo', $estado, route('quiz.show', $modulo), $accesoTotal));
            }

            $actual ??= $nodos->firstWhere('estado', self::ACTUAL);

            return [
                'modulo' => $modulo,
                'estado' => $estadoModulo,
                'nodos' => $nodos->values(),
                'lecciones' => $modulo->lecciones->count(),
                'vistas' => $modulo->lecciones->filter(fn (Leccion $leccion) => $vistas->has($leccion->id))->count(),
            ];
        });

        $encuesta = null;

        if ($estadoEncuesta !== null) {
            $pendiente = $estadoEncuesta === 'pendiente';
            $estado = $pendiente ? $this->siguienteEstado($hayActual) : self::COMPLETADO;

            $encuesta = $this->nodo('encuesta', $area->id, 'Encuesta de satisfacción', $pendiente ? '3 preguntas rápidas' : 'Respondida', $estado, $pendiente ? route('areas.encuesta', $area) : null, $accesoTotal);
            $actual ??= $estado === self::ACTUAL ? $encuesta : null;
        }

        return [
            'modulos' => $tramos->values(),
            'encuesta' => $encuesta,
            'actual' => $actual,
        ];
    }

    /**
     * El primer paso pendiente es el actual; los siguientes (de módulos abiertos) quedan disponibles.
     */
    private function siguienteEstado(bool &$hayActual): string
    {
        if ($hayActual) {
            return self::DISPONIBLE;
        }

        $hayActual = true;

        return self::ACTUAL;
    }

    /**
     * @return array<string, mixed>
     */
    private function nodo(string $tipo, int $id, string $titulo, string $etiqueta, string $estado, ?string $url, bool $accesoTotal): array
    {
        $clicable = $url !== null && ($estado !== self::BLOQUEADO || $accesoTotal);

        return [
            'tipo' => $tipo,
            'id' => $id,
            'titulo' => $titulo,
            'etiqueta' => $etiqueta,
            'estado' => $estado,
            'url' => $clicable ? $url : null,
        ];
    }

    /**
     * Ids de los quizzes de estos módulos que el usuario aprobó, indexados para consulta rápida.
     *
     * @param  Collection<int, Modulo>  $modulos
     * @return Collection<int, int>
     */
    private function quizzesAprobados(User $usuario, Collection $modulos): Collection
    {
        $quizIds = $modulos->map(fn (Modulo $modulo) => $modulo->quiz?->id)->filter()->values()->all();

        return IntentoQuiz::query()
            ->where('user_id', $usuario->id)
            ->where('aprobado', true)
            ->whereIn('quiz_id', $quizIds)
            ->pluck('quiz_id')
            ->unique()
            ->flip();
    }
}
