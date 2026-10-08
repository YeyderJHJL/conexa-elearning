<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cálculo del avance de un usuario (0-100) por módulo, área y global.
 *
 * Reglas: solo cuentan módulos y lecciones activos; una lección completada que luego se
 * desactiva deja de contar; el contenido vacío (módulo sin lecciones activas, área sin
 * módulos con lecciones) no entra en los promedios; los porcentajes se redondean hacia
 * abajo para que 100 signifique realmente todo completado.
 */
class ProgresoService
{
    /**
     * Avance de un módulo.
     */
    public function modulo(User $user, Modulo $modulo): int
    {
        $fila = $this->consulta($user)->where('modulos.id', $modulo->id)->first();

        return $fila ? $this->entero($this->razon($fila) ?? 0.0) : 0;
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
     * Total de lecciones activas y completadas por el usuario, por cada módulo activo.
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
            ->groupBy('modulos.id', 'modulos.area_id')
            ->selectRaw('modulos.id, modulos.area_id, count(leccions.id) as total, count(leccion_user.leccion_id) as completadas');
    }

    /**
     * Porcentaje (0-100) de una fila, o null si el módulo no tiene lecciones activas.
     */
    private function razon(object $fila): ?float
    {
        return $fila->total > 0 ? $fila->completadas / $fila->total * 100 : null;
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
