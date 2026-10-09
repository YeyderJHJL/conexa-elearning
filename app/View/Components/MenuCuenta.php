<?php

namespace App\View\Components;

use App\Models\User;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

/**
 * Contenido del menú del botón de usuario: fondo claro/oscuro, perfil y cerrar sesión.
 */
class MenuCuenta extends Component
{
    public User $usuario;

    public function __construct(Request $request)
    {
        $this->usuario = $request->user();
    }

    public function render(): View|Closure|string
    {
        return view('components.menu-cuenta');
    }
}
