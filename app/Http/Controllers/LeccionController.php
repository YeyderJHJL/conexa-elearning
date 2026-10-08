<?php

namespace App\Http\Controllers;

use App\Models\Leccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
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
    public function completar(Request $request, Leccion $leccion): RedirectResponse
    {
        $this->autorizarAcceso($request, $leccion);

        $request->user()->lecciones()->syncWithoutDetaching([$leccion->id]);

        [, $siguiente] = $this->vecinas($leccion);

        return $siguiente
            ? redirect()->route('lecciones.show', $siguiente)
            : redirect()->route('modulos.show', $leccion->modulo_id);
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
     * Exige acceso al área de la lección; para trabajadores, además, que todo esté activo.
     */
    private function autorizarAcceso(Request $request, Leccion $leccion): void
    {
        $leccion->loadMissing('modulo.area');

        $this->authorize('view', $leccion->modulo->area);

        abort_unless(
            ($leccion->activa && $leccion->modulo->activo && $leccion->modulo->area->activa) || $request->user()->esAdmin(),
            404
        );
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
