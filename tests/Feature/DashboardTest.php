<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_worker_sees_only_assigned_areas_with_active_module_count(): void
    {
        $worker = User::factory()->create(['rol' => 'trabajador']);
        $assigned = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas', 'descripcion' => 'Todo sobre ventas', 'icono' => 'bi-cart']);
        Area::create(['nombre' => 'Seguridad industrial', 'slug' => 'seguridad']);
        $worker->areas()->attach($assigned);

        Modulo::create(['area_id' => $assigned->id, 'titulo' => 'Intro', 'activo' => true]);
        Modulo::create(['area_id' => $assigned->id, 'titulo' => 'Avanzado', 'activo' => true]);
        Modulo::create(['area_id' => $assigned->id, 'titulo' => 'Borrador', 'activo' => false]);

        $this->actingAs($worker)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Ventas')
            ->assertSee('Todo sobre ventas')
            ->assertSee('bi-cart')
            ->assertSee('2 módulos')
            ->assertDontSee('Seguridad industrial');
    }

    public function test_worker_without_areas_sees_friendly_message(): void
    {
        $worker = User::factory()->create(['rol' => 'trabajador']);

        $this->actingAs($worker)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Aún no tienes áreas asignadas');
    }
}
