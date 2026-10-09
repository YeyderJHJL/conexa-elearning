<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Resumen de avance y notas de varios trabajadores para el panel del admin.
 */
class ReporteAdminService
{
    public function __construct(private readonly ProgresoService $progreso) {}

    /**
     * Para cada trabajador: avance global, quizzes rendidos y aprobados (cada quiz cuenta una vez,
     * con su mejor intento) y promedio de esas mejores notas.
     *
     * @param  Collection<int, User>  $usuarios
     * @return Collection<int, array{global: int, rendidos: int, aprobados: int, promedio: int|null}>
     */
    public function resumenTrabajadores(Collection $usuarios): Collection
    {
        $mejores = DB::table('intentos_quiz')
            ->whereIn('user_id', $usuarios->pluck('id')->all())
            ->groupBy('user_id', 'quiz_id')
            ->selectRaw('user_id, quiz_id, max(puntaje) as mejor, max(aprobado) as aprobado')
            ->get()
            ->groupBy('user_id');

        return $usuarios->mapWithKeys(function (User $usuario) use ($mejores) {
            $quizzes = $mejores->get($usuario->id, collect());

            return [$usuario->id => [
                'global' => $this->progreso->global($usuario),
                'rendidos' => $quizzes->count(),
                'aprobados' => $quizzes->filter(fn (object $quiz) => (bool) $quiz->aprobado)->count(),
                'promedio' => $quizzes->isEmpty() ? null : (int) floor($quizzes->avg('mejor')),
            ]];
        });
    }
}
