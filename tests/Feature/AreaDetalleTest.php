<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaDetalleTest extends TestCase
{
    use RefreshDatabase;

    private function trabajadorConArea(array $area = []): array
    {
        $trabajador = User::factory()->create(['rol' => 'trabajador']);
        $area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas'] + $area);
        $trabajador->areas()->attach($area);

        return [$trabajador, $area];
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);

        $this->get(route('areas.show', $area))->assertRedirect('/login');
    }

    public function test_worker_cannot_open_an_unassigned_area(): void
    {
        [$trabajador] = $this->trabajadorConArea();
        $ajena = Area::create(['nombre' => 'Seguridad', 'slug' => 'seguridad']);

        $this->actingAs($trabajador)->get(route('areas.show', $ajena))->assertForbidden();
    }

    public function test_worker_sees_active_modules_in_order_with_provisional_status(): void
    {
        [$trabajador, $area] = $this->trabajadorConArea();

        $segundo = Modulo::create(['area_id' => $area->id, 'titulo' => 'Segundo modulo', 'orden' => 2]);
        $primero = Modulo::create(['area_id' => $area->id, 'titulo' => 'Primer modulo', 'orden' => 1, 'descripcion' => 'Descripcion uno']);
        Modulo::create(['area_id' => $area->id, 'titulo' => 'Modulo oculto', 'orden' => 3, 'activo' => false]);

        Leccion::create(['modulo_id' => $primero->id, 'titulo' => 'L1', 'activa' => true]);
        Leccion::create(['modulo_id' => $primero->id, 'titulo' => 'L2', 'activa' => true]);
        Leccion::create(['modulo_id' => $primero->id, 'titulo' => 'L3', 'activa' => false]);

        $this->actingAs($trabajador)
            ->get(route('areas.show', $area))
            ->assertOk()
            ->assertSeeInOrder(['Primer modulo', 'En curso', 'Segundo modulo', 'Bloqueado'])
            ->assertSee('Descripcion uno')
            ->assertSee('2 lecciones')
            ->assertDontSee('Modulo oculto');
    }

    public function test_worker_gets_not_found_for_an_inactive_assigned_area(): void
    {
        [$trabajador, $area] = $this->trabajadorConArea(['activa' => false]);

        $this->actingAs($trabajador)->get(route('areas.show', $area))->assertNotFound();
    }

    public function test_dashboard_cards_link_to_the_area_detail(): void
    {
        [$trabajador, $area] = $this->trabajadorConArea();

        $this->actingAs($trabajador)
            ->get('/dashboard')
            ->assertSee(route('areas.show', $area), false);
    }
}
