<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use App\Services\ProgresoService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Si el módulo sigue bloqueado para el trabajador, lo devuelve al área con un aviso.
     * Los administradores pueden entrar siempre.
     */
    protected function exigirModuloDesbloqueado(Request $request, Modulo $modulo): void
    {
        $usuario = $request->user();

        if ($usuario->esAdmin() || app(ProgresoService::class)->moduloDesbloqueado($usuario, $modulo)) {
            return;
        }

        throw new HttpResponseException(
            redirect()
                ->route('areas.show', $modulo->area_id)
                ->with('aviso', "El módulo «{$modulo->titulo}» está bloqueado: completa primero el módulo anterior.")
        );
    }
}
