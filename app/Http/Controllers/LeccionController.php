<?php

namespace App\Http\Controllers;

use App\Models\Leccion;
use App\Services\ProgresoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeccionController extends Controller
{
    /**
     * Vista de una lección: video, contenido, PDF y navegación.
     */
    public function show(Request $request, Leccion $leccion): View
    {
        $this->autorizarAcceso($request, $leccion);

        [$anterior, $siguiente] = $this->vecinas($leccion);

        return view('lecciones.show', [
            'leccion' => $leccion,
            'modulo' => $leccion->modulo,
            'anterior' => $anterior,
            'siguiente' => $siguiente,
            'completada' => $request->user()->lecciones()->whereKey($leccion->id)->exists(),
        ]);
    }

    /**
     * Registra la lección como completada y avanza a la siguiente (o al módulo si era la última).
     */
    public function completar(Request $request, Leccion $leccion, ProgresoService $progreso): RedirectResponse
    {
        $this->autorizarAcceso($request, $leccion);

        $moduloCompletadoAntes = $progreso->moduloCompletado($request->user(), $leccion->modulo);

        $request->user()->lecciones()->syncWithoutDetaching([$leccion->id]);

        [, $siguiente] = $this->vecinas($leccion);

        $redireccion = $siguiente
            ? redirect()->route('lecciones.show', $siguiente)
            : redirect()->route('modulos.show', $leccion->modulo_id);

        // Solo detalle visual: celebra una vez, cuando esta lección es la que completa el módulo.
        if (! $moduloCompletadoAntes && $progreso->moduloCompletado($request->user(), $leccion->modulo)) {
            $redireccion->with('celebracion', [
                'titulo' => '¡Módulo completado!',
                'mensaje' => "Terminaste todas las lecciones de «{$leccion->modulo->titulo}». ¡Buen trabajo!",
            ]);
        }

        return $redireccion;
    }

    /**
     * Sirve el PDF adjunto (en el navegador o como descarga) solo a quien puede ver la lección.
     */
    public function pdf(Request $request, Leccion $leccion): StreamedResponse
    {
        $this->autorizarAcceso($request, $leccion);

        $disco = Storage::disk(config('filament.default_filesystem_disk'));

        abort_unless(filled($leccion->archivo_pdf) && $disco->exists($leccion->archivo_pdf), 404);

        $nombre = str($leccion->titulo)->slug()->append('.pdf')->toString();

        return $request->boolean('descargar')
            ? $disco->download($leccion->archivo_pdf, $nombre)
            : $disco->response($leccion->archivo_pdf, $nombre);
    }

    /**
     * Sirve el video subido, solo a quien puede ver la lección. Usa BinaryFileResponse para que el
     * navegador pueda adelantar y retroceder (peticiones HTTP Range).
     */
    public function video(Request $request, Leccion $leccion): BinaryFileResponse
    {
        $this->autorizarAcceso($request, $leccion);

        $disco = Storage::disk(config('filament.default_filesystem_disk'));

        abort_unless(filled($leccion->archivo_video) && $disco->exists($leccion->archivo_video), 404);

        return response()->file($disco->path($leccion->archivo_video), [
            'Content-Type' => $disco->mimeType($leccion->archivo_video) ?: 'video/mp4',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Exige acceso al área de la lección; para trabajadores, además, que todo esté activo
     * y que el módulo no esté bloqueado por la secuencia.
     */
    private function autorizarAcceso(Request $request, Leccion $leccion): void
    {
        $leccion->loadMissing('modulo.area');

        $this->authorize('view', $leccion->modulo->area);

        abort_unless(
            ($leccion->activa && $leccion->modulo->activo && $leccion->modulo->area->activa) || $request->user()->esAdmin(),
            404
        );

        $this->exigirModuloDesbloqueado($request, $leccion->modulo);
    }

    /**
     * Lecciones activas vecinas dentro del módulo.
     *
     * @return array{0: Leccion|null, 1: Leccion|null}
     */
    private function vecinas(Leccion $leccion): array
    {
        $lecciones = $leccion->modulo->lecciones()->where('activa', true)->get()->values();
        $posicion = $lecciones->search(fn (Leccion $item) => $item->is($leccion));

        if ($posicion === false) {
            return [null, null];
        }

        return [$lecciones->get($posicion - 1), $lecciones->get($posicion + 1)];
    }
}
