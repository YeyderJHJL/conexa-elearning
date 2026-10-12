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
     * El colaborador eligió ver el área como ruta: ahí "volver" debe llevar al camino, no a la página suelta del módulo.
     */
    protected function enRuta(Request $request): bool
    {
        return $request->session()->get('vista_area') === 'ruta';
    }

    /**
     * A dónde vuelve el colaborador desde una lección o un quiz: la ruta del área anclada al módulo
     * (modo ruta) o la página del módulo (modo lista).
     */
    protected function urlDelModulo(Request $request, Modulo $modulo): string
    {
        return $this->enRuta($request)
            ? route('areas.show', $modulo->area_id).'#modulo-'.$modulo->id
            : route('modulos.show', $modulo);
    }

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
