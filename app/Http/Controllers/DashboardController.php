<?php

namespace App\Http\Controllers;

use App\Services\ProgresoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Pantalla de inicio del trabajador: sus áreas asignadas y su avance.
     */
    public function __invoke(Request $request, ProgresoService $progreso): View
    {
        $areas = $request->user()
            ->areas()
            ->where('activa', true)
            ->withCount(['modulos as modulos_activos_count' => fn ($query) => $query->where('activo', true)])
            ->orderBy('orden')
            ->get();

        $resumen = $progreso->resumen($request->user(), $areas);

        return view('dashboard', [
            'areas' => $areas,
            'progresoAreas' => $resumen['areas'],
            'progresoGlobal' => $resumen['global'],
        ]);
    }
}
