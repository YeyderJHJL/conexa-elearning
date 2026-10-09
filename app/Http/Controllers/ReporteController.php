<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReporteController extends Controller
{
    /**
     * Reporte de avance general de un trabajador (PDF), solo para administradores.
     *
     * No comprueba permisos por sí mismo: la ruta `reportes.trabajador` lo protege con el middleware `admin`.
     */
    public function __invoke(User $usuario, ReporteService $reporte): Response
    {
        $nombre = sprintf('reporte-capacitacion-%s-%s.pdf', str($usuario->name)->slug(), now()->format('Ymd'));

        return Pdf::loadView('pdf.reporte', $reporte->datosPara($usuario))
            ->setPaper('a4')
            ->download($nombre);
    }
}
