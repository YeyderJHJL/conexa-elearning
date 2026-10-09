<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Services\ProgresoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AreaController extends Controller
{
    /**
     * Detalle de un área: sus módulos activos con su avance y su estado en la secuencia.
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
            'modulos' => $modulos,
            'progresoModulos' => $progreso->porModulos($request->user(), $area),
            'estadosModulos' => $progreso->estados($request->user(), $area),
            'esAdmin' => $request->user()->esAdmin(),
            'puedeDescargarResumen' => filled($area->resumen_pdf)
                && ($request->user()->esAdmin() || $progreso->areaCompletada($request->user(), $area)),
        ]);
    }
}
