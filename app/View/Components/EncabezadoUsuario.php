<?php

namespace App\View\Components;

use App\Services\ProgresoService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Bloque del usuario en el encabezado: avatar con iniciales, nombre y avance global.
 */
class EncabezadoUsuario extends Component
{
    public string $nombre;

    public ?string $cargo;

    public string $iniciales;

    public int $avance;

    public function __construct(ProgresoService $progreso)
    {
        $usuario = auth()->user();

        $this->nombre = $usuario->name;
        $this->cargo = $usuario->cargo;
        $this->iniciales = self::calcularIniciales($usuario->name);
        $this->avance = $progreso->global($usuario);
    }

    /**
     * Iniciales de las dos primeras palabras del nombre, en mayúscula ("María del Carmen" -> "MD").
     */
    public static function calcularIniciales(string $nombre): string
    {
        $palabras = preg_split('/\s+/u', trim($nombre), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $iniciales = collect($palabras)
            ->take(2)
            ->map(fn (string $palabra) => mb_strtoupper(mb_substr($palabra, 0, 1)))
            ->implode('');

        return $iniciales !== '' ? $iniciales : '?';
    }

    public function render(): View|Closure|string
    {
        return view('components.encabezado-usuario');
    }
}
