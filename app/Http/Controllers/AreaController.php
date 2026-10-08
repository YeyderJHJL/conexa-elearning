<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Modulo;
use App\Services\ProgresoService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AreaController extends Controller
{
    /**
     * Detalle de un área: sus módulos activos con un estado provisional.
     */
    public function show(Request $request, Area $area, ProgresoService $progreso): View
    {
        $this->authorize('view', $area);

        abort_unless($area->activa || $request->user()->esAdmin(), 404);

        $modulos = $area->modulos()
            ->where('activo', true)
            ->withCount(['lecciones as lecciones_activas_count' => fn ($query) => $query->where('activa', true)])
            ->get();

        return view('areas.show', [
            'area' => $area,
            'modulos' => $this->asignarEstados($modulos),
            'progresoModulos' => $progreso->porModulos($request->user(), $area),
        ]);
    }

    /**
     * Estado provisional de cada módulo: el primero queda disponible y el resto bloqueado.
     * Se reemplazará por el cálculo real de progreso (ProgresoService).
     *
     * @param  Collection<int, Modulo>  $modulos
     * @return Collection<int, Modulo>
     */
    private function asignarEstados(Collection $modulos): Collection
    {
        return $modulos->each(function ($modulo, int $indice) {
            $modulo->estado = $indice === 0 ? 'disponible' : 'bloqueado';
        });
    }
}
