<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Pantalla de inicio del trabajador: sus áreas asignadas.
     */
    public function __invoke(Request $request): View
    {
        $areas = $request->user()
            ->areas()
            ->where('activa', true)
            ->withCount(['modulos as modulos_activos_count' => fn ($query) => $query->where('activo', true)])
            ->orderBy('orden')
            ->get();

        return view('dashboard', ['areas' => $areas]);
    }
}
