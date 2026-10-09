<?php

namespace App\Http\Controllers;

use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReporteController extends Controller
{
    /**
     * Descarga el reporte de capacitación en PDF del propio trabajador.
     */
    public function __invoke(Request $request, ReporteService $reporte): Response
    {
        $usuario = $request->user();

        $nombre = sprintf('reporte-capacitacion-%s-%s.pdf', str($usuario->name)->slug(), now()->format('Ymd'));

        return Pdf::loadView('pdf.reporte', $reporte->datosPara($usuario))
            ->setPaper('a4')
            ->download($nombre);
    }
}
