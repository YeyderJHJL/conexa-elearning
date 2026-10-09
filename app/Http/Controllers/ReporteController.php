<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReporteController extends Controller
{
    /**
     * Reporte de avance general de un trabajador (PDF).
     *
     * RESERVADO PARA EL PANEL DE ADMIN: no tiene ruta a propósito. Al conectarlo hay que
     * protegerlo con el middleware `admin`, porque no comprueba permisos por sí mismo.
     */
    public function __invoke(User $usuario, ReporteService $reporte): Response
    {
        $nombre = sprintf('reporte-capacitacion-%s-%s.pdf', str($usuario->name)->slug(), now()->format('Ymd'));

        return Pdf::loadView('pdf.reporte', $reporte->datosPara($usuario))
            ->setPaper('a4')
            ->download($nombre);
    }
}
