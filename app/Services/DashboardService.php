<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Números reales del dashboard del admin. El avance sale de ProgresoService; aquí solo se agrega
 * por trabajador, área y estado, y se leen las fechas de actividad para las mini-tendencias.
 */
class DashboardService
{
    public const SEMANAS = 7;

    public const COMPLETADO = 'completado';

    public const EN_PROGRESO = 'en_progreso';

    public const SIN_INICIAR = 'sin_iniciar';

    public function __construct(private readonly ProgresoService $progreso) {}

    /**
     * Trabajadores activos con sus áreas activas asignadas.
     *
     * @return Collection<int, User>
     */
    public function trabajadores(): Collection
    {
        return User::query()
            ->where('rol', 'trabajador')
            ->where('activo', true)
            ->with(['areas' => fn ($query) => $query->where('activa', true)])
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{
     *     trabajadores: int,
     *     nuevosSemana: int,
     *     areasActivas: int,
     *     modulosActivos: int,
     *     leccionesActivas: int,
     *     avancePromedio: int|null,
     *     aprobacion: array{tasa: int|null, aprobados: int, rendidos: int},
     *     estados: array{completado: int, en_progreso: int, sin_iniciar: int},
     *     avancePorArea: Collection<int, array{area: Area, promedio: int|null, trabajadores: int}>,
     *     tendencias: array{trabajadores: list<int>, lecciones: list<int>, aprobados: list<int>},
     *     leccionesSemana: int
     * }
     */
    public function datos(): array
    {
        $trabajadores = $this->trabajadores();
        $ids = $trabajadores->pluck('id')->all();
        $actividad = $this->ultimaActividad($ids);

        $filas = $trabajadores->map(function (User $trabajador) use ($actividad) {
            $resumen = $this->progreso->resumen($trabajador, $trabajador->areas);

            return [
                'global' => $resumen['global'],
                'areas' => $resumen['areas'],
                'tieneAreas' => $trabajador->areas->isNotEmpty(),
                'estado' => $this->estado($resumen['global'], $trabajador->areas->isNotEmpty(), $actividad->get($trabajador->id)),
            ];
        });

        $conAreas = $filas->where('tieneAreas', true);

        $leccionesCompletadas = $this->fechas('leccion_user', 'completada_en', $ids);
        $quizzesAprobados = $this->fechas('intentos_quiz', 'creado_en', $ids, fn ($consulta) => $consulta->where('aprobado', true));
        $lecciones = $this->porSemana($leccionesCompletadas);

        return [
            'trabajadores' => $trabajadores->count(),
            'nuevosSemana' => $trabajadores->filter(fn (User $trabajador) => $trabajador->created_at?->gte(now()->subDays(7)))->count(),
            'areasActivas' => Area::query()->where('activa', true)->count(),
            'modulosActivos' => Modulo::query()->where('activo', true)->count(),
            'leccionesActivas' => Leccion::query()->where('activa', true)->count(),
            'avancePromedio' => $conAreas->isEmpty() ? null : (int) floor($conAreas->avg('global')),
            'aprobacion' => $this->aprobacion($ids),
            'estados' => [
                self::COMPLETADO => $filas->where('estado', self::COMPLETADO)->count(),
                self::EN_PROGRESO => $filas->where('estado', self::EN_PROGRESO)->count(),
                self::SIN_INICIAR => $filas->where('estado', self::SIN_INICIAR)->count(),
            ],
            'avancePorArea' => $this->avancePorArea($filas),
            'tendencias' => [
                'trabajadores' => $this->acumuladoPorSemana($trabajadores->pluck('created_at')->filter()->all()),
                'lecciones' => $lecciones,
                'aprobados' => $this->porSemana($quizzesAprobados),
            ],
            'leccionesSemana' => end($lecciones) ?: 0,
        ];
    }

    /**
     * Avance, estado y última actividad de un solo trabajador (para filas de tabla).
     *
     * @return array{global: int, estado: string, ultima: Carbon|null}
     */
    public function paraTrabajador(User $trabajador): array
    {
        $areas = $trabajador->areas->where('activa', true)->values();
        $ultima = $this->ultimaActividad([$trabajador->id])->get($trabajador->id);
        $global = $this->progreso->resumen($trabajador, $areas)['global'];

        return [
            'global' => $global,
            'estado' => $this->estado($global, $areas->isNotEmpty(), $ultima),
            'ultima' => $ultima,
        ];
    }

    /**
     * Completado con avance 100 %; en progreso si ya hubo actividad; sin iniciar si nunca la hubo.
     */
    public function estado(int $global, bool $tieneAreas, ?Carbon $ultimaActividad): string
    {
        if ($tieneAreas && $global >= 100) {
            return self::COMPLETADO;
        }

        return $ultimaActividad ? self::EN_PROGRESO : self::SIN_INICIAR;
    }

    /**
     * Última actividad real por trabajador: su última lección completada o su último quiz rendido.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, Carbon|null>
     */
    public function ultimaActividad(array $ids): Collection
    {
        $lecciones = DB::table('leccion_user')->whereIn('user_id', $ids)->groupBy('user_id')
            ->selectRaw('user_id, max(completada_en) as ultima')->pluck('ultima', 'user_id');

        $intentos = DB::table('intentos_quiz')->whereIn('user_id', $ids)->groupBy('user_id')
            ->selectRaw('user_id, max(creado_en) as ultima')->pluck('ultima', 'user_id');

        return collect($ids)->mapWithKeys(fn (int $id) => [
            $id => collect([$lecciones->get($id), $intentos->get($id)])
                ->filter()
                ->map(fn ($fecha) => Carbon::parse($fecha))
                ->max(),
        ]);
    }

    /**
     * Tasa de aprobación: de los quizzes que cada trabajador rindió, cuántos aprobó en algún intento.
     *
     * @param  array<int, int>  $ids
     * @return array{tasa: int|null, aprobados: int, rendidos: int}
     */
    private function aprobacion(array $ids): array
    {
        $pares = DB::table('intentos_quiz')
            ->whereIn('user_id', $ids)
            ->groupBy('user_id', 'quiz_id')
            ->selectRaw('max(aprobado) as aprobado')
            ->get();

        $rendidos = $pares->count();
        $aprobados = $pares->filter(fn (object $par) => (bool) $par->aprobado)->count();

        return [
            'tasa' => $rendidos === 0 ? null : intdiv($aprobados * 100, $rendidos),
            'aprobados' => $aprobados,
            'rendidos' => $rendidos,
        ];
    }

    /**
     * Promedio de avance de cada área activa entre los trabajadores que la tienen asignada.
     *
     * @param  Collection<int, array<string, mixed>>  $filas
     * @return Collection<int, array{area: Area, promedio: int|null, trabajadores: int}>
     */
    private function avancePorArea(Collection $filas): Collection
    {
        $porArea = [];

        foreach ($filas as $fila) {
            foreach ($fila['areas'] as $areaId => $porcentaje) {
                $porArea[$areaId][] = $porcentaje;
            }
        }

        return Area::query()->where('activa', true)->orderBy('orden')->orderBy('id')->get()
            ->map(fn (Area $area) => [
                'area' => $area,
                'promedio' => isset($porArea[$area->id]) ? (int) floor(collect($porArea[$area->id])->avg()) : null,
                'trabajadores' => count($porArea[$area->id] ?? []),
            ]);
    }

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, Carbon>
     */
    private function fechas(string $tabla, string $columna, array $ids, ?callable $filtro = null): Collection
    {
        $consulta = DB::table($tabla)
            ->whereIn('user_id', $ids)
            ->where($columna, '>=', now()->subWeeks(self::SEMANAS));

        if ($filtro) {
            $filtro($consulta);
        }

        return $consulta->pluck($columna)->map(fn ($fecha) => Carbon::parse($fecha));
    }

    /**
     * Cantidad de fechas en cada una de las últimas SEMANAS semanas (la última posición es la semana actual).
     *
     * @param  Collection<int, Carbon>  $fechas
     * @return list<int>
     */
    private function porSemana(Collection $fechas): array
    {
        $semanas = array_fill(0, self::SEMANAS, 0);

        foreach ($fechas as $fecha) {
            $atras = intdiv((int) floor($fecha->diffInDays(now(), true)), 7);

            if ($atras < self::SEMANAS) {
                $semanas[self::SEMANAS - 1 - $atras]++;
            }
        }

        return $semanas;
    }

    /**
     * Total acumulado al final de cada una de las últimas SEMANAS semanas.
     *
     * @param  array<int, Carbon>  $fechas
     * @return list<int>
     */
    private function acumuladoPorSemana(array $fechas): array
    {
        $acumulado = [];

        for ($i = self::SEMANAS - 1; $i >= 0; $i--) {
            $corte = now()->subDays($i * 7);
            $acumulado[] = collect($fechas)->filter(fn (Carbon $fecha) => $fecha->lte($corte))->count();
        }

        return $acumulado;
    }
}
