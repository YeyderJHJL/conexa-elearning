<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\User;
use App\Services\ProgresoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProgresoServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $trabajador;

    private ProgresoService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trabajador = User::factory()->create(['rol' => 'trabajador']);
        $this->servicio = app(ProgresoService::class);
    }

    private function area(string $nombre, bool $asignada = true): Area
    {
        $area = Area::create(['nombre' => $nombre, 'slug' => str($nombre)->slug()]);

        if ($asignada) {
            $this->trabajador->areas()->attach($area);
        }

        return $area;
    }

    /**
     * Crea un módulo con $total lecciones activas, de las cuales $completadas están completadas por el trabajador.
     */
    private function modulo(Area $area, int $total, int $completadas = 0, array $atributos = []): Modulo
    {
        $modulo = Modulo::create($atributos + ['area_id' => $area->id, 'titulo' => 'Modulo '.fake()->unique()->word()]);

        for ($i = 0; $i < $total; $i++) {
            $leccion = Leccion::create(['modulo_id' => $modulo->id, 'titulo' => "Leccion {$i}", 'activa' => true]);

            if ($i < $completadas) {
                $this->trabajador->lecciones()->attach($leccion);
            }
        }

        return $modulo;
    }

    public function test_module_progress_is_completed_over_active_lessons(): void
    {
        $area = $this->area('Ventas');

        $this->assertSame(0, $this->servicio->modulo($this->trabajador, $this->modulo($area, 4, 0)));
        $this->assertSame(50, $this->servicio->modulo($this->trabajador, $this->modulo($area, 4, 2)));
        $this->assertSame(100, $this->servicio->modulo($this->trabajador, $this->modulo($area, 3, 3)));
    }

    public function test_module_without_lessons_has_zero_progress(): void
    {
        $this->assertSame(0, $this->servicio->modulo($this->trabajador, $this->modulo($this->area('Ventas'), 0)));
    }

    public function test_inactive_lessons_do_not_count_even_if_they_were_completed(): void
    {
        $modulo = $this->modulo($this->area('Ventas'), 2, 1);
        $oculta = Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'Oculta', 'activa' => false]);
        $this->trabajador->lecciones()->attach($oculta);

        $this->assertSame(50, $this->servicio->modulo($this->trabajador, $modulo));
    }

    public function test_progress_of_other_users_is_ignored(): void
    {
        $modulo = $this->modulo($this->area('Ventas'), 2, 0);
        $otro = User::factory()->create(['rol' => 'trabajador']);
        $otro->lecciones()->attach($modulo->lecciones()->pluck('id'));

        $this->assertSame(0, $this->servicio->modulo($this->trabajador, $modulo));
        $this->assertSame(100, $this->servicio->modulo($otro, $modulo));
    }

    public function test_percentage_never_rounds_up_to_one_hundred(): void
    {
        $modulo = $this->modulo($this->area('Ventas'), 200, 199);

        $this->assertSame(99, $this->servicio->modulo($this->trabajador, $modulo));
    }

    public function test_area_progress_is_the_average_of_active_modules_ignoring_empty_ones(): void
    {
        $area = $this->area('Ventas');
        $this->modulo($area, 2, 2);
        $this->modulo($area, 2, 0);
        $this->modulo($area, 0);
        $this->modulo($area, 2, 0, ['activo' => false]);

        $this->assertSame(50, $this->servicio->area($this->trabajador, $area));
    }

    public function test_global_progress_averages_only_assigned_areas_with_content(): void
    {
        $completa = $this->area('Ventas');
        $mitad = $this->area('Seguridad');
        $vacia = $this->area('Vacia');
        $ajena = $this->area('Ajena', asignada: false);
        $this->modulo($completa, 2, 2);
        $this->modulo($mitad, 2, 1);
        $this->modulo($ajena, 2, 0);

        $this->assertSame(75, $this->servicio->global($this->trabajador));
        $this->assertSame(0, $this->servicio->area($this->trabajador, $vacia));
    }

    public function test_global_progress_is_zero_without_assigned_areas(): void
    {
        $this->assertSame(0, $this->servicio->global($this->trabajador));
    }

    public function test_summary_uses_a_single_query_regardless_of_the_number_of_areas(): void
    {
        $areas = collect(['A', 'B', 'C', 'D'])->map(function (string $nombre) {
            $area = $this->area($nombre);
            $this->modulo($area, 2, 1);
            $this->modulo($area, 3, 3);

            return $area;
        });

        DB::enableQueryLog();
        $resumen = $this->servicio->resumen($this->trabajador, $areas);

        $this->assertCount(1, DB::getQueryLog());
        $this->assertSame(75, $resumen['global']);
        $this->assertSame([75, 75, 75, 75], $resumen['areas']->values()->all());
    }

    public function test_dashboard_shows_global_and_per_area_progress(): void
    {
        $ventas = $this->area('Ventas');
        $seguridad = $this->area('Seguridad');
        $this->modulo($ventas, 2, 2);
        $this->modulo($seguridad, 4, 1);

        $this->actingAs($this->trabajador)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Tu avance global')
            ->assertSee('62%')
            ->assertSee('100%')
            ->assertSee('25%')
            ->assertSee('width: 25%', false);
    }

    public function test_area_detail_shows_module_progress_and_completed_mark(): void
    {
        $area = $this->area('Ventas');
        $this->modulo($area, 2, 2, ['titulo' => 'Modulo terminado']);
        $this->modulo($area, 4, 1, ['titulo' => 'Modulo a medias']);

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', $area))
            ->assertOk()
            ->assertSeeInOrder(['Modulo terminado', 'Completado', '100%', 'Modulo a medias', 'Bloqueado', '25%'])
            ->assertDontSee('Disponible');
    }
}
