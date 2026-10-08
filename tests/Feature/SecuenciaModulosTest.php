<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\User;
use App\Services\ProgresoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecuenciaModulosTest extends TestCase
{
    use RefreshDatabase;

    private User $trabajador;

    private Area $area;

    private ProgresoService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trabajador = User::factory()->create(['rol' => 'trabajador']);
        $this->area = $this->area('Ventas');
        $this->servicio = app(ProgresoService::class);
    }

    private function area(string $nombre, int $orden = 0): Area
    {
        $area = Area::create(['nombre' => $nombre, 'slug' => str($nombre)->slug(), 'orden' => $orden]);
        $this->trabajador->areas()->attach($area);

        return $area;
    }

    /**
     * Crea un módulo con $total lecciones activas; las primeras $completadas quedan vistas por el trabajador.
     */
    private function modulo(int $total, int $completadas = 0, array $atributos = [], ?Area $area = null): Modulo
    {
        $modulo = Modulo::create($atributos + ['area_id' => ($area ?? $this->area)->id, 'titulo' => 'Modulo '.fake()->unique()->word()]);

        for ($i = 0; $i < $total; $i++) {
            $leccion = Leccion::create(['modulo_id' => $modulo->id, 'titulo' => "Leccion {$i}", 'orden' => $i, 'activa' => true]);

            if ($i < $completadas) {
                $this->trabajador->lecciones()->attach($leccion);
            }
        }

        return $modulo;
    }

    public function test_states_follow_the_sequence(): void
    {
        $primero = $this->modulo(2, 2);
        $segundo = $this->modulo(2, 1);
        $tercero = $this->modulo(1, 0);

        $this->assertSame([
            $primero->id => 'completado',
            $segundo->id => 'en_curso',
            $tercero->id => 'bloqueado',
        ], $this->servicio->estados($this->trabajador, $this->area)->all());
    }

    public function test_first_module_is_always_unlocked_and_the_next_opens_when_the_previous_is_complete(): void
    {
        $primero = $this->modulo(2, 0);
        $segundo = $this->modulo(1, 0);

        $this->assertTrue($this->servicio->moduloDesbloqueado($this->trabajador, $primero));
        $this->assertFalse($this->servicio->moduloDesbloqueado($this->trabajador, $segundo));

        $this->trabajador->lecciones()->attach($primero->lecciones()->pluck('id'));

        $this->assertTrue($this->servicio->moduloDesbloqueado($this->trabajador, $segundo));
    }

    public function test_the_sequence_follows_orden_and_then_id(): void
    {
        $segundo = $this->modulo(1, 0, ['orden' => 2]);
        $primero = $this->modulo(1, 0, ['orden' => 1]);
        $empatado = $this->modulo(1, 0, ['orden' => 2]);

        $this->assertTrue($this->servicio->moduloDesbloqueado($this->trabajador, $primero));
        $this->assertFalse($this->servicio->moduloDesbloqueado($this->trabajador, $segundo));
        $this->assertFalse($this->servicio->moduloDesbloqueado($this->trabajador, $empatado));

        $this->trabajador->lecciones()->attach($primero->lecciones()->pluck('id'));

        $this->assertTrue($this->servicio->moduloDesbloqueado($this->trabajador, $segundo));
        $this->assertFalse($this->servicio->moduloDesbloqueado($this->trabajador, $empatado));
    }

    public function test_empty_and_inactive_modules_do_not_block_the_sequence(): void
    {
        $this->modulo(3, 0, ['activo' => false]);
        $vacio = $this->modulo(0);
        $real = $this->modulo(2, 0);

        $this->assertTrue($this->servicio->moduloDesbloqueado($this->trabajador, $vacio));
        $this->assertTrue($this->servicio->moduloDesbloqueado($this->trabajador, $real));
    }

    public function test_inactive_module_is_never_unlocked(): void
    {
        $inactivo = $this->modulo(1, 0, ['activo' => false]);

        $this->assertFalse($this->servicio->moduloDesbloqueado($this->trabajador, $inactivo));
    }

    public function test_next_lesson_is_the_first_pending_one_of_the_first_available_module(): void
    {
        $this->modulo(2, 2);
        $actual = $this->modulo(3, 1);
        $this->modulo(2, 0);

        $siguiente = $this->servicio->siguienteLeccion($this->trabajador);

        $this->assertSame($actual->lecciones->get(1)->id, $siguiente->id);
        $this->assertSame($actual->id, $siguiente->modulo->id);
    }

    public function test_next_lesson_skips_finished_areas_and_is_null_when_nothing_is_pending(): void
    {
        $this->area->update(['orden' => 1]);
        $this->modulo(2, 2);
        $otra = $this->area('Seguridad', orden: 2);
        $pendiente = $this->modulo(2, 0, area: $otra);

        $this->assertSame($pendiente->lecciones->first()->id, $this->servicio->siguienteLeccion($this->trabajador)->id);

        $this->trabajador->lecciones()->attach($pendiente->lecciones()->pluck('id'));

        $this->assertNull($this->servicio->siguienteLeccion($this->trabajador));
    }

    public function test_next_lesson_is_null_without_areas(): void
    {
        $sinAreas = User::factory()->create(['rol' => 'trabajador']);

        $this->assertNull($this->servicio->siguienteLeccion($sinAreas));
    }

    public function test_locked_module_redirects_to_the_area_with_a_notice(): void
    {
        $this->modulo(1, 0);
        $bloqueado = $this->modulo(1, 0, ['titulo' => 'Modulo cerrado']);

        $this->actingAs($this->trabajador)
            ->get(route('modulos.show', $bloqueado))
            ->assertRedirect(route('areas.show', $this->area))
            ->assertSessionHas('aviso');

        $this->followingRedirects()
            ->get(route('modulos.show', $bloqueado))
            ->assertOk()
            ->assertSee('Modulo cerrado');
    }

    public function test_lessons_of_a_locked_module_cannot_be_opened_completed_or_downloaded(): void
    {
        $this->modulo(1, 0);
        $bloqueado = $this->modulo(1, 0);
        $leccion = $bloqueado->lecciones->first();
        $leccion->update(['archivo_pdf' => 'lecciones/x.pdf']);

        $this->actingAs($this->trabajador);

        $this->get(route('lecciones.show', $leccion))->assertRedirect(route('areas.show', $this->area));
        $this->post(route('lecciones.completar', $leccion))->assertRedirect(route('areas.show', $this->area));
        $this->get(route('lecciones.pdf', $leccion))->assertRedirect(route('areas.show', $this->area));
        $this->assertSame(0, $this->trabajador->lecciones()->count());
    }

    public function test_module_opens_once_the_previous_one_is_complete(): void
    {
        $this->modulo(1, 1);
        $abierto = $this->modulo(1, 0);

        $this->actingAs($this->trabajador)->get(route('modulos.show', $abierto))->assertOk();
    }

    public function test_admin_can_enter_locked_modules(): void
    {
        $this->modulo(1, 0);
        $bloqueado = $this->modulo(1, 0);
        $admin = User::factory()->create(['rol' => 'admin']);

        $this->actingAs($admin)
            ->get(route('modulos.show', $bloqueado))
            ->assertOk();
    }

    public function test_area_detail_shows_real_states_and_locked_modules_have_no_link(): void
    {
        $completo = $this->modulo(1, 1, ['titulo' => 'Modulo hecho']);
        $enCurso = $this->modulo(2, 1, ['titulo' => 'Modulo actual']);
        $bloqueado = $this->modulo(1, 0, ['titulo' => 'Modulo futuro']);

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', $this->area))
            ->assertOk()
            ->assertSeeInOrder(['Modulo hecho', 'Completado', 'Modulo actual', 'En curso', 'Modulo futuro', 'Bloqueado'])
            ->assertSee(route('modulos.show', $completo), false)
            ->assertSee(route('modulos.show', $enCurso), false)
            ->assertDontSee(route('modulos.show', $bloqueado), false)
            ->assertSee('bi-lock-fill', false);
    }

    public function test_dashboard_shows_the_continue_button_only_when_there_is_something_pending(): void
    {
        $modulo = $this->modulo(2, 1);

        $this->actingAs($this->trabajador)
            ->get('/dashboard')
            ->assertSee('Continuar donde quedaste')
            ->assertSee(route('lecciones.show', $modulo->lecciones->get(1)), false);

        $this->trabajador->lecciones()->attach($modulo->lecciones->get(1));

        $this->get('/dashboard')->assertDontSee('Continuar donde quedaste');
    }
}
