<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Feedback;
use App\Models\User;
use App\Services\ProgresoService;
use App\Services\RutaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AreaController extends Controller
{
    public const VISTAS = ['lista', 'ruta'];

    /**
     * Detalle de un área: sus módulos activos en modo lista o en modo ruta (preferencia guardada en sesión).
     */
    public function show(Request $request, Area $area, ProgresoService $progreso, RutaService $ruta): View
    {
        $usuario = $request->user();

        $this->authorize('view', $area);

        abort_unless($area->activa || $usuario->esAdmin(), 404);

        $vista = $this->vistaElegida($request);

        $modulos = $area->modulos()
            ->where('activo', true)
            ->withCount(['lecciones as lecciones_activas_count' => fn ($query) => $query->where('activa', true)])
            ->get();

        $completada = $progreso->areaCompletada($usuario, $area);
        $estadoEncuesta = $this->estadoEncuesta($usuario, $area, $completada);

        return view('areas.show', [
            'area' => $area,
            'vista' => $vista,
            'modulos' => $modulos,
            'progresoModulos' => $progreso->porModulos($usuario, $area),
            'estadosModulos' => $progreso->estados($usuario, $area),
            'ruta' => $vista === 'ruta' ? $ruta->paraArea($usuario, $area, $usuario->esAdmin(), $estadoEncuesta) : null,
            'esAdmin' => $usuario->esAdmin(),
            'puedeDescargarResumen' => filled($area->resumen_pdf) && ($usuario->esAdmin() || $completada),
            'estadoEncuesta' => $estadoEncuesta,
        ]);
    }

    /**
     * Modo de vista: el pedido con ?vista= (que se recuerda en sesión) o el último elegido; por defecto, lista.
     */
    private function vistaElegida(Request $request): string
    {
        $pedida = $request->query('vista');

        if (is_string($pedida) && in_array($pedida, self::VISTAS, true)) {
            $request->session()->put('vista_area', $pedida);
        }

        return $request->session()->get('vista_area', 'lista');
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
