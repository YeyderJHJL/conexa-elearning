<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuloController extends Controller
{
    /**
     * Detalle de un módulo: sus lecciones activas y cuáles completó el usuario.
     */
    public function show(Request $request, Modulo $modulo): View
    {
        $modulo->load('area');

        $this->authorize('view', $modulo->area);

        abort_unless(
            ($modulo->activo && $modulo->area->activa) || $request->user()->esAdmin(),
            404
        );

        $lecciones = $modulo->lecciones()->where('activa', true)->get();

        $completadas = $request->user()
            ->lecciones()
            ->whereIn('leccions.id', $lecciones->modelKeys())
            ->pluck('leccions.id');

        return view('modulos.show', [
            'modulo' => $modulo,
            'area' => $modulo->area,
            'lecciones' => $lecciones,
            'completadas' => $completadas,
        ]);
    }
}
