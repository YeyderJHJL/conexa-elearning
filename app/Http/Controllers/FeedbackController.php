<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Feedback;
use App\Services\ProgresoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    /**
     * Pantalla de la encuesta de un área ya completada y todavía sin responder.
     */
    public function create(Request $request, Area $area, ProgresoService $progreso): View|RedirectResponse
    {
        if ($redireccion = $this->exigirAreaCompletada($request, $area, $progreso)) {
            return $redireccion;
        }

        if (Feedback::where('user_id', $request->user()->id)->where('area_id', $area->id)->exists()) {
            return redirect()
                ->route('areas.show', $area)
                ->with('aviso', 'Ya habías respondido la encuesta de esta área.');
        }

        return view('areas.encuesta', ['area' => $area]);
    }

    /**
     * Guarda la encuesta de un área ya completada. Una sola respuesta por trabajador y área.
     */
    public function store(Request $request, Area $area, ProgresoService $progreso): RedirectResponse
    {
        if ($redireccion = $this->exigirAreaCompletada($request, $area, $progreso)) {
            return $redireccion;
        }

        $usuario = $request->user();

        $datos = $request->validate([
            'claridad' => ['required', 'integer', 'between:1,5'],
            'utilidad' => ['required', 'integer', 'between:1,5'],
            'ritmo' => ['required', 'integer', 'between:1,5'],
            'comentario' => ['nullable', 'string', 'max:600'],
        ], [
            'claridad.required' => 'Califica la claridad del contenido.',
            'utilidad.required' => 'Califica la utilidad del contenido.',
            'ritmo.required' => 'Califica el ritmo del área.',
            'between' => 'Elige una nota entre 1 y 5.',
            'integer' => 'Elige una nota entre 1 y 5.',
            'comentario.max' => 'El comentario no puede superar los 600 caracteres.',
        ]);

        $datos['comentario'] = filled($datos['comentario'] ?? null) ? trim($datos['comentario']) : null;

        $feedback = Feedback::firstOrCreate(
            ['user_id' => $usuario->id, 'area_id' => $area->id],
            $datos
        );

        return redirect()
            ->route('areas.show', $area)
            ->with(
                $feedback->wasRecentlyCreated ? 'estado' : 'aviso',
                $feedback->wasRecentlyCreated ? '¡Gracias por tu opinión!' : 'Ya habías respondido la encuesta de esta área.'
            );
    }

    /**
     * Comprueba el acceso al área y que esté completada; si aún no, devuelve al área con un aviso.
     */
    private function exigirAreaCompletada(Request $request, Area $area, ProgresoService $progreso): ?RedirectResponse
    {
        $usuario = $request->user();

        $this->authorize('view', $area);

        abort_unless($area->activa || $usuario->esAdmin(), 404);

        if ($progreso->areaCompletada($usuario, $area)) {
            return null;
        }

        return redirect()
            ->route('areas.show', $area)
            ->with('aviso', 'Podrás responder la encuesta cuando completes el área.');
    }
}
