<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cálculo del avance de un usuario (0-100) por módulo, área y global, y de la secuencia de módulos.
 *
 * Un módulo está completo cuando se vieron todas sus lecciones activas y, si tiene un quiz
 * con preguntas, el usuario aprobó al menos un intento. Para el porcentaje, el quiz cuenta
 * como un elemento más del módulo (junto a sus lecciones).
 *
 * Reglas: solo cuentan módulos y lecciones activos; una lección completada que luego se
 * desactiva deja de contar; el contenido vacío (módulo sin lecciones activas ni quiz, área
 * sin módulos con contenido) no entra en los promedios ni bloquea la secuencia; los
 * porcentajes se redondean hacia abajo para que 100 signifique realmente todo completado.
 */
class ProgresoService
{
    public const COMPLETADO = 'completado';

    public const EN_CURSO = 'en_curso';

    public const BLOQUEADO = 'bloqueado';

    /**
     * Avance de un módulo.
     */
    public function modulo(User $user, Modulo $modulo): int
    {
        $fila = $this->consulta($user)->where('modulos.id', $modulo->id)->first();

        return $fila ? $this->entero($this->razon($fila) ?? 0.0) : 0;
    }

    /**
     * Si el usuario vio todas las lecciones activas del módulo (un módulo sin lecciones cuenta como visto).
     */
    public function leccionesCompletadas(User $user, Modulo $modulo): bool
    {
        $fila = $this->consulta($user)->where('modulos.id', $modulo->id)->first();

        return $fila !== null && $fila->completadas >= $fila->total;
    }

    /**
     * Avance de cada módulo activo de un área, indexado por id de módulo.
     *
     * @return Collection<int, int>
     */
    public function porModulos(User $user, Area $area): Collection
    {
        return $this->consulta($user)
            ->where('modulos.area_id', $area->id)
            ->get()
            ->mapWithKeys(fn (object $fila) => [(int) $fila->id => $this->entero($this->razon($fila) ?? 0.0)]);
    }

    /**
     * Avance de un área: promedio del avance de sus módulos activos.
     */
    public function area(User $user, Area $area): int
    {
        return $this->resumen($user, collect([$area]))['areas'][$area->id];
    }

    /**
     * Avance global: promedio del avance de las áreas activas asignadas al usuario.
     *
     * @param  Collection<int, Area>|null  $areas  Áreas ya cargadas, para no volver a consultarlas.
     */
    public function global(User $user, ?Collection $areas = null): int
    {
        $areas ??= $user->areas()->where('activa', true)->get();

        return $this->resumen($user, $areas)['global'];
    }

    /**
     * Avance de varias áreas y el global con una sola consulta agregada.
     *
     * @param  Collection<int, Area>  $areas
     * @return array{areas: Collection<int, int>, global: int}
     */
    public function resumen(User $user, Collection $areas): array
    {
        $filas = $this->consulta($user)
            ->whereIn('modulos.area_id', $areas->pluck('id')->all())
            ->get()
            ->groupBy('area_id');

        $promedios = $areas->mapWithKeys(fn (Area $area) => [
            $area->id => $this->promedio($filas->get($area->id, collect())->map(fn (object $fila) => $this->razon($fila))),
        ]);

        return [
            'areas' => $promedios->map(fn (?float $valor) => $this->entero($valor ?? 0.0)),
            'global' => $this->entero($this->promedio($promedios) ?? 0.0),
        ];
    }

    /**
     * Estado de cada módulo activo de un área para el usuario, indexado por id de módulo:
     * completado, en_curso o bloqueado.
     *
     * @return Collection<int, string>
     */
    public function estados(User $user, Area $area): Collection
    {
        return ($this->evaluar($user, [$area->id])->get($area->id) ?? collect())
            ->mapWithKeys(fn (object $fila) => [(int) $fila->id => $fila->estado]);
    }

    /**
     * Si el usuario puede entrar al módulo: el primero del área siempre, y los demás
     * cuando el módulo anterior está completo (lecciones vistas y quiz aprobado).
     */
    public function moduloDesbloqueado(User $user, Modulo $modulo): bool
    {
        $fila = $this->evaluar($user, [$modulo->area_id])->get($modulo->area_id)?->firstWhere('id', $modulo->id);

        return $fila !== null && $fila->estado !== self::BLOQUEADO;
    }

    /**
     * Siguiente paso del usuario: la primera lección sin ver del primer módulo disponible
     * (recorriendo sus áreas en orden) o, si ya vio todas las lecciones de ese módulo,
     * su quiz pendiente. Null si no le queda nada pendiente.
     *
     * @param  Collection<int, Area>|null  $areas  Áreas activas asignadas, ya ordenadas; si no, se consultan.
     * @return array{tipo: 'leccion', leccion: Leccion}|array{tipo: 'quiz', modulo: Modulo}|null
     */
    public function siguientePaso(User $user, ?Collection $areas = null): ?array
    {
        $areas ??= $user->areas()->where('activa', true)->orderBy('orden')->orderBy('id')->get();
        $porArea = $this->evaluar($user, $areas->pluck('id')->all());

        foreach ($areas as $area) {
            $fila = $porArea->get($area->id)?->first(
                fn (object $fila) => $fila->estado === self::EN_CURSO && ($fila->total > 0 || $fila->tiene_quiz)
            );

            if (! $fila) {
                continue;
            }

            if ($fila->completadas < $fila->total) {
                $leccion = Leccion::query()
                    ->with('modulo')
                    ->where('modulo_id', $fila->id)
                    ->where('activa', true)
                    ->whereNotIn('id', fn ($query) => $query->select('leccion_id')->from('leccion_user')->where('user_id', $user->id))
                    ->orderBy('orden')
                    ->orderBy('id')
                    ->first();

                if ($leccion) {
                    return ['tipo' => 'leccion', 'leccion' => $leccion];
                }
            }

            if ($fila->tiene_quiz) {
                return ['tipo' => 'quiz', 'modulo' => Modulo::with('quiz')->find($fila->id)];
            }
        }

        return null;
    }

    /**
     * Módulos activos de las áreas dadas, ordenados dentro de cada área, con su estado en la secuencia.
     *
     * @param  array<int, int>  $areaIds
     * @return Collection<int, Collection<int, object>>
     */
    private function evaluar(User $user, array $areaIds): Collection
    {
        return $this->consulta($user)
            ->whereIn('modulos.area_id', $areaIds)
            ->get()
            ->groupBy('area_id')
            ->map(function (Collection $filas) {
                $anteriorPasable = true;

                return $filas->map(function (object $fila) use (&$anteriorPasable) {
                    $completo = $this->moduloCompleto($fila);

                    $fila->estado = match (true) {
                        ! $anteriorPasable => self::BLOQUEADO,
                        $completo => self::COMPLETADO,
                        default => self::EN_CURSO,
                    };

                    // Un módulo sin contenido no puede completarse, así que no frena la secuencia.
                    $anteriorPasable = $anteriorPasable && ($completo || $this->vacio($fila));

                    return $fila;
                });
            });
    }

    /**
     * Un módulo está completo cuando se vieron todas sus lecciones activas y, si tiene un quiz
     * con preguntas, el usuario aprobó al menos un intento.
     */
    private function moduloCompleto(object $fila): bool
    {
        if ($this->vacio($fila)) {
            return false;
        }

        return $fila->completadas >= $fila->total && (! $fila->tiene_quiz || $fila->quiz_aprobado);
    }

    /**
     * Módulo sin lecciones activas ni quiz con preguntas: no hay nada que completar.
     */
    private function vacio(object $fila): bool
    {
        return $fila->total == 0 && ! $fila->tiene_quiz;
    }

    /**
     * Lecciones activas, lecciones vistas por el usuario, si el módulo tiene un quiz con preguntas
     * y si el usuario aprobó ese quiz, por cada módulo activo, en orden.
     */
    private function consulta(User $user): Builder
    {
        return DB::table('modulos')
            ->leftJoin('leccions', function (JoinClause $join) {
                $join->on('leccions.modulo_id', '=', 'modulos.id')->where('leccions.activa', '=', true);
            })
            ->leftJoin('leccion_user', function (JoinClause $join) use ($user) {
                $join->on('leccion_user.leccion_id', '=', 'leccions.id')->where('leccion_user.user_id', '=', $user->id);
            })
            ->where('modulos.activo', true)
            ->groupBy('modulos.id', 'modulos.area_id', 'modulos.orden')
            ->orderBy('modulos.orden')
            ->orderBy('modulos.id')
            ->selectRaw(
                'modulos.id, modulos.area_id,
                count(leccions.id) as total,
                count(leccion_user.leccion_id) as completadas,
                exists (select 1 from quizzes where quizzes.modulo_id = modulos.id
                    and exists (select 1 from preguntas where preguntas.quiz_id = quizzes.id)) as tiene_quiz,
                exists (select 1 from intentos_quiz inner join quizzes on quizzes.id = intentos_quiz.quiz_id
                    where quizzes.modulo_id = modulos.id and intentos_quiz.user_id = ? and intentos_quiz.aprobado = 1) as quiz_aprobado',
                [$user->id]
            );
    }

    /**
     * Porcentaje (0-100) de una fila, o null si el módulo no tiene contenido.
     * El quiz cuenta como un elemento más junto a las lecciones.
     */
    private function razon(object $fila): ?float
    {
        $elementos = $fila->total + ($fila->tiene_quiz ? 1 : 0);

        if ($elementos == 0) {
            return null;
        }

        $hechos = $fila->completadas + ($fila->tiene_quiz && $fila->quiz_aprobado ? 1 : 0);

        return $hechos / $elementos * 100;
    }

    /**
     * Promedio ignorando los valores null (contenido vacío); null si no queda ninguno.
     *
     * @param  Collection<int|string, float|null>  $valores
     */
    private function promedio(Collection $valores): ?float
    {
        return $valores->reject(fn (?float $valor) => $valor === null)->avg();
    }

    private function entero(float $valor): int
    {
        return (int) floor($valor + 1e-9);
    }
}
