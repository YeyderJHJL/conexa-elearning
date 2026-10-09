<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Feedback;
use App\Models\User;
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
        $usuario = $request->user();

        $this->authorize('view', $area);

        abort_unless($area->activa || $usuario->esAdmin(), 404);

        $modulos = $area->modulos()
            ->where('activo', true)
            ->withCount(['lecciones as lecciones_activas_count' => fn ($query) => $query->where('activa', true)])
            ->get();

        $completada = $progreso->areaCompletada($usuario, $area);

        return view('areas.show', [
            'area' => $area,
            'modulos' => $modulos,
            'progresoModulos' => $progreso->porModulos($usuario, $area),
            'estadosModulos' => $progreso->estados($usuario, $area),
            'esAdmin' => $usuario->esAdmin(),
            'puedeDescargarResumen' => filled($area->resumen_pdf) && ($usuario->esAdmin() || $completada),
            'estadoEncuesta' => $this->estadoEncuesta($usuario, $area, $completada),
        ]);
    }

    /**
     * Paso final de la lista: la encuesta solo existe para trabajadores que completaron el área.
     *
     * @return 'pendiente'|'respondida'|null
     */
    private function estadoEncuesta(User $usuario, Area $area, bool $completada): ?string
    {
        if ($usuario->esAdmin() || ! $completada) {
            return null;
        }

        return Feedback::where('user_id', $usuario->id)->where('area_id', $area->id)->exists() ? 'respondida' : 'pendiente';
    }
}
