<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Modulo;
use App\Services\ProgresoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DescargaResumenController extends Controller
{
    public function __construct(private readonly ProgresoService $progreso) {}

    /**
     * Descarga el PDF de resumen que el admin subió para un módulo, una vez completado.
     */
    public function modulo(Request $request, Modulo $modulo): StreamedResponse|RedirectResponse
    {
        $usuario = $request->user();

        $modulo->load('area');

        $this->authorize('view', $modulo->area);

        abort_unless(($modulo->activo && $modulo->area->activa) || $usuario->esAdmin(), 404);

        $this->exigirModuloDesbloqueado($request, $modulo);

        abort_unless(filled($modulo->resumen_pdf), 404);

        if (! $usuario->esAdmin() && ! $this->progreso->moduloCompletado($usuario, $modulo)) {
            return redirect()
                ->route('modulos.show', $modulo)
                ->with('aviso', 'Podrás descargar el resumen cuando completes el módulo.');
        }

        return $this->descargar($modulo->resumen_pdf, 'resumen-modulo-'.str($modulo->titulo)->slug().'.pdf');
    }

    /**
     * Descarga el PDF de resumen que el admin subió para un área, una vez completada.
     */
    public function area(Request $request, Area $area): StreamedResponse|RedirectResponse
    {
        $usuario = $request->user();

        $this->authorize('view', $area);

        abort_unless($area->activa || $usuario->esAdmin(), 404);

        abort_unless(filled($area->resumen_pdf), 404);

        if (! $usuario->esAdmin() && ! $this->progreso->areaCompletada($usuario, $area)) {
            return redirect()
                ->route('areas.show', $area)
                ->with('aviso', 'Podrás descargar el resumen cuando completes el área.');
        }

        return $this->descargar($area->resumen_pdf, 'resumen-area-'.str($area->nombre)->slug().'.pdf');
    }

    /**
     * Sirve el archivo desde el disco privado donde Filament lo guarda; nunca expone una URL pública.
     */
    private function descargar(string $ruta, string $nombre): StreamedResponse
    {
        $disco = Storage::disk(config('filament.default_filesystem_disk'));

        abort_unless($disco->exists($ruta), 404);

        return $disco->download($ruta, $nombre);
    }
}
