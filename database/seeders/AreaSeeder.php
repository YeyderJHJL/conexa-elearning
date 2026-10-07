<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = [
            ['nombre' => 'Bienvenida y reglas', 'slug' => 'bienvenida', 'descripcion' => 'Quiénes somos, normas y políticas.', 'icono' => 'bi-hand-thumbs-up', 'orden' => 1],
            ['nombre' => 'Administrativa', 'slug' => 'administrativa', 'descripcion' => 'Procesos, documentos y RRHH.', 'icono' => 'bi-folder2-open', 'orden' => 2],
            ['nombre' => 'Ventas', 'slug' => 'ventas', 'descripcion' => 'Producto, proceso comercial y atención.', 'icono' => 'bi-graph-up-arrow', 'orden' => 3],
        ];
        foreach ($areas as $a) { \App\Models\Area::updateOrCreate(['slug' => $a['slug']], $a); }
    }
}
