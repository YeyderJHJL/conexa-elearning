<?php

namespace App\View\Components;

use App\Models\Area;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Services\RutaService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Component;

/**
 * Menú hamburguesa de la barra: módulos, lecciones y quiz del área que se está viendo, para saltar
 * a cualquiera en dos pasos. Los estados salen de RutaService, así que coinciden con la lista y la ruta.
 */
class MenuContenido extends Component
{
    public ?Area $area = null;

    /** @var array<string, mixed>|null */
    public ?array $ruta = null;

    public ?int $moduloActualId = null;

    public ?int $leccionActualId = null;

    public bool $enQuiz = false;

    public function __construct(Request $request, RutaService $rutas)
    {
        $usuario = $request->user();
        $route = $request->route();

        if (! $usuario || ! $route) {
            return;
        }

        $leccion = $route->parameter('leccion');
        $modulo = $route->parameter('modulo');
        $area = $route->parameter('area');

        $leccion = $leccion instanceof Leccion ? $leccion->loadMissing('modulo') : null;
        $modulo = $modulo instanceof Modulo ? $modulo : $leccion?->modulo;
        $area = $area instanceof Area ? $area : $modulo?->loadMissing('area')->area;

        if (! $area || ! Gate::forUser($usuario)->allows('view', $area) || (! $area->activa && ! $usuario->esAdmin())) {
            return;
        }

        $this->area = $area;
        $this->ruta = $rutas->paraArea($usuario, $area, $usuario->esAdmin());
        $this->leccionActualId = $leccion?->id;
        $this->enQuiz = str_starts_with((string) $route->getName(), 'quiz.');
        $this->moduloActualId = $modulo?->id
            ?? $this->ruta['modulos']->first(fn (array $tramo) => $tramo['nodos']->contains('estado', RutaService::ACTUAL))['modulo']->id
            ?? null;
    }

    public function render(): View|Closure|string
    {
        return $this->area ? view('components.menu-contenido') : '';
    }
}
