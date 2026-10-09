<?php

namespace Tests\Feature;

use App\Filament\Widgets\AvancePorAreaChart;
use App\Filament\Widgets\EstadoTrabajadoresChart;
use App\Filament\Widgets\ResumenGeneral;
use App\Filament\Widgets\TrabajadoresAvance;
use App\Models\Area;
use App\Models\IntentoQuiz;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Quiz;
use App\Models\User;
use App\Services\DashboardService;
use Filament\Facades\Filament;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class DashboardAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['name' => 'Admin', 'rol' => 'admin']);
    }

    private function area(string $nombre, array $atributos = []): Area
    {
        return Area::create($atributos + ['nombre' => $nombre, 'slug' => str($nombre)->slug()]);
    }

    private function trabajador(string $nombre, array $areas = [], array $atributos = []): User
    {
        $usuario = User::factory()->create($atributos + ['name' => $nombre, 'rol' => 'trabajador', 'cargo' => 'Asesor']);
        $usuario->areas()->attach(collect($areas)->map->id->all());

        return $usuario;
    }

    /**
     * @return array{0: Modulo, 1: Collection, 2: Quiz}
     */
    private function moduloConQuiz(Area $area, int $lecciones = 2): array
    {
        $modulo = Modulo::create(['area_id' => $area->id, 'titulo' => 'Modulo de '.$area->nombre]);
        $leccionesCreadas = collect(range(1, $lecciones))->map(
            fn (int $i) => Leccion::create(['modulo_id' => $modulo->id, 'titulo' => "Leccion {$i}", 'orden' => $i, 'activa' => true])
        );

        $quiz = Quiz::create(['modulo_id' => $modulo->id, 'titulo' => 'Quiz']);
        $pregunta = Pregunta::create(['quiz_id' => $quiz->id, 'enunciado' => 'Pregunta', 'orden' => 1]);
        Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'Correcta', 'es_correcta' => true]);
        Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'Incorrecta', 'es_correcta' => false]);

        return [$modulo, $leccionesCreadas, $quiz];
    }

    private function ver(User $usuario, iterable $lecciones, ?Carbon $cuando = null): void
    {
        foreach ($lecciones as $leccion) {
            $usuario->lecciones()->attach($leccion, ['completada_en' => ($cuando ?? now())->toDateTimeString()]);
        }
    }

    private function intento(User $usuario, Quiz $quiz, bool $aprobado): void
    {
        IntentoQuiz::factory()->create([
            'user_id' => $usuario->id,
            'quiz_id' => $quiz->id,
            'puntaje' => $aprobado ? 90 : 30,
            'aprobado' => $aprobado,
        ]);
    }

    /**
     * Escenario: dos áreas activas (A con quiz, B con una lección) y una inactiva; ana (A y B), luis (A),
     * maria (A, todo completo), carla (sin áreas), pedro (inactivo) y un admin.
     *
     * @return array<string, mixed>
     */
    private function escenario(): array
    {
        $a = $this->area('Ventas', ['orden' => 1]);
        $b = $this->area('Seguridad', ['orden' => 2]);
        $inactiva = $this->area('Archivada', ['orden' => 3, 'activa' => false]);

        [$moduloA, $leccionesA, $quizA] = $this->moduloConQuiz($a);
        $moduloB = Modulo::create(['area_id' => $b->id, 'titulo' => 'Modulo B']);
        $leccionB = Leccion::create(['modulo_id' => $moduloB->id, 'titulo' => 'Leccion B', 'activa' => true]);

        $ana = $this->trabajador('Ana', [$a, $b]);
        $luis = $this->trabajador('Luis', [$a]);
        $maria = $this->trabajador('Maria', [$a]);
        $carla = $this->trabajador('Carla');
        $pedro = $this->trabajador('Pedro', [$a], ['activo' => false]);

        $this->ver($ana, $leccionesA);
        $this->intento($ana, $quizA, true);

        $this->ver($luis, [$leccionesA->first()]);
        $this->intento($luis, $quizA, false);

        $this->ver($maria, $leccionesA);
        $this->intento($maria, $quizA, true);

        $this->intento($pedro, $quizA, true);

        return compact('a', 'b', 'inactiva', 'ana', 'luis', 'maria', 'carla', 'pedro', 'leccionB');
    }

    private function datos(): array
    {
        return app(DashboardService::class)->datos();
    }

    // -------------------------------------------------------------- servicio

    public function test_headline_numbers_only_count_active_workers_and_active_areas(): void
    {
        $this->escenario();

        $datos = $this->datos();

        $this->assertSame(4, $datos['trabajadores'], 'Ana, Luis, Maria y Carla; no Pedro (inactivo) ni el admin.');
        $this->assertSame(4, $datos['nuevosSemana']);
        $this->assertSame(2, $datos['areasActivas']);
        $this->assertSame(2, $datos['modulosActivos']);
        $this->assertSame(3, $datos['leccionesActivas']);
    }

    public function test_average_progress_uses_the_real_progress_of_workers_with_areas(): void
    {
        $this->escenario();

        // Ana: área A 100 %, área B 0 % -> 50. Luis: 1 de 3 elementos -> 33. Maria: 100. Carla no tiene áreas.
        $this->assertSame(61, $this->datos()['avancePromedio']);
    }

    public function test_quiz_approval_rate_counts_each_worker_quiz_pair_once_and_ignores_inactive_workers(): void
    {
        $this->escenario();

        $this->assertSame(['tasa' => 66, 'aprobados' => 2, 'rendidos' => 3], $this->datos()['aprobacion']);
    }

    public function test_approval_rate_is_null_without_attempts_and_average_is_null_without_workers_with_areas(): void
    {
        $this->trabajador('Solo');

        $datos = $this->datos();

        $this->assertNull($datos['aprobacion']['tasa']);
        $this->assertNull($datos['avancePromedio']);
        $this->assertSame(1, $datos['estados']['sin_iniciar']);
    }

    public function test_workers_are_split_into_completed_in_progress_and_not_started(): void
    {
        $this->escenario();

        $this->assertSame(['completado' => 1, 'en_progreso' => 2, 'sin_iniciar' => 1], $this->datos()['estados']);
    }

    public function test_status_rules(): void
    {
        $servicio = app(DashboardService::class);
        $actividad = now();

        $this->assertSame('completado', $servicio->estado(100, true, $actividad));
        $this->assertSame('en_progreso', $servicio->estado(100, false, $actividad), 'Sin áreas no puede estar completado.');
        $this->assertSame('en_progreso', $servicio->estado(0, true, $actividad), 'Un quiz fallido ya es actividad.');
        $this->assertSame('en_progreso', $servicio->estado(40, true, $actividad));
        $this->assertSame('sin_iniciar', $servicio->estado(0, true, null));
        $this->assertSame('sin_iniciar', $servicio->estado(0, false, null));
    }

    public function test_average_progress_by_area_covers_active_areas_in_order(): void
    {
        $datos = $this->escenario();

        $areas = $this->datos()['avancePorArea'];

        $this->assertSame(['Ventas', 'Seguridad'], $areas->map(fn ($fila) => $fila['area']->nombre)->all());
        $this->assertSame([77, 0], $areas->pluck('promedio')->all());
        $this->assertSame([3, 1], $areas->pluck('trabajadores')->all());
        $this->assertNotContains($datos['inactiva']->id, $areas->map(fn ($fila) => $fila['area']->id)->all());
    }

    public function test_an_area_without_assigned_workers_has_no_average(): void
    {
        $this->area('Sin gente');

        $fila = $this->datos()['avancePorArea']->first();

        $this->assertNull($fila['promedio']);
        $this->assertSame(0, $fila['trabajadores']);
    }

    public function test_last_activity_is_the_latest_lesson_or_quiz_attempt(): void
    {
        $datos = $this->escenario();

        $servicio = app(DashboardService::class);
        $actividad = $servicio->ultimaActividad([$datos['ana']->id, $datos['carla']->id]);

        $this->assertNotNull($actividad->get($datos['ana']->id));
        $this->assertNull($actividad->get($datos['carla']->id));

        $antiguo = $this->trabajador('Antiguo', [$datos['a']]);
        $leccion = Leccion::where('titulo', 'Leccion 1')->first();
        $antiguo->lecciones()->attach($leccion, ['completada_en' => '2026-01-10 08:00:00']);
        $this->intento($antiguo, Quiz::first(), false);

        $ultima = $servicio->ultimaActividad([$antiguo->id])->get($antiguo->id);

        $this->assertTrue($ultima->gt(Carbon::parse('2026-01-10 08:00:00')), 'Gana el intento de quiz, que es más reciente.');
    }

    public function test_weekly_trends_bucket_real_dates_into_the_last_seven_weeks(): void
    {
        $area = $this->area('Ventas');
        [, $lecciones, $quiz] = $this->moduloConQuiz($area, 3);
        $reciente = $this->trabajador('Reciente', [$area]);
        $antiguo = $this->trabajador('Antiguo', [$area], []);
        $antiguo->forceFill(['created_at' => now()->subDays(30)])->save();

        $this->ver($reciente, [$lecciones[0]], now()->subDay());
        $this->ver($reciente, [$lecciones[1]], now()->subDays(10));
        $this->ver($reciente, [$lecciones[2]], now()->subDays(60));
        IntentoQuiz::factory()->create(['user_id' => $reciente->id, 'quiz_id' => $quiz->id, 'puntaje' => 90, 'aprobado' => true, 'creado_en' => now()->subDays(15)]);

        $tendencias = $this->datos()['tendencias'];

        $this->assertSame([0, 0, 0, 0, 0, 1, 1], $tendencias['lecciones'], 'La de hace 60 días queda fuera de las 7 semanas.');
        $this->assertSame([0, 0, 0, 0, 1, 0, 0], $tendencias['aprobados']);
        $this->assertSame([0, 0, 1, 1, 1, 1, 2], $tendencias['trabajadores'], 'Acumulado al cierre de cada semana: el de hace 30 días aún no existía hace 42 ni 35 días.');
        $this->assertSame(1, $this->datos()['leccionesSemana']);
    }

    public function test_single_worker_summary_matches_the_progress_service(): void
    {
        $datos = $this->escenario();

        $maria = app(DashboardService::class)->paraTrabajador($datos['maria']->load('areas'));
        $carla = app(DashboardService::class)->paraTrabajador($datos['carla']->load('areas'));

        $this->assertSame(100, $maria['global']);
        $this->assertSame('completado', $maria['estado']);
        $this->assertNotNull($maria['ultima']);
        $this->assertSame(0, $carla['global']);
        $this->assertSame('sin_iniciar', $carla['estado']);
        $this->assertNull($carla['ultima']);
    }

    // --------------------------------------------------------------- dashboard

    public function test_default_widgets_are_gone_and_only_the_four_dashboard_widgets_remain(): void
    {
        $widgets = collect(Filament::getPanel('admin')->getWidgets())->sort()->values()->all();

        $this->assertSame([
            AvancePorAreaChart::class,
            EstadoTrabajadoresChart::class,
            ResumenGeneral::class,
            TrabajadoresAvance::class,
        ], $widgets);

        $this->assertNotContains(AccountWidget::class, $widgets);
        $this->assertNotContains(FilamentInfoWidget::class, $widgets);
    }

    public function test_dashboard_mounts_the_four_widgets_lazily_and_nothing_from_filament_defaults(): void
    {
        $this->escenario();

        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Panel de control')
            ->assertSee('Avance real de la capacitación de tu equipo')
            ->assertSeeLivewire(ResumenGeneral::class)
            ->assertSeeLivewire(AvancePorAreaChart::class)
            ->assertSeeLivewire(EstadoTrabajadoresChart::class)
            ->assertSeeLivewire(TrabajadoresAvance::class)
            ->assertDontSee('filament.com')
            ->assertDontSee('Welcome');
    }

    public function test_dashboard_is_not_available_to_workers_or_guests(): void
    {
        $trabajador = $this->trabajador('Ana');

        $this->get('/admin')->assertRedirect();

        $this->actingAs($trabajador)->get('/admin')->assertForbidden();
    }

    public function test_stats_widget_shows_the_real_numbers_with_icons_colors_and_trends(): void
    {
        $this->escenario();

        $html = Livewire::actingAs($this->admin)->test(ResumenGeneral::class)
            ->assertSee('Trabajadores activos')
            ->assertSee('+4 nuevos en 7 días')
            ->assertSee('2 módulos · 3 lecciones activas')
            ->assertSee('61%')
            ->assertSee('5 lecciones completadas esta semana')
            ->assertSee('66%')
            ->assertSee('2 de 3 quizzes rendidos aprobados')
            ->html();

        $this->assertStringNotContainsString('wire:poll', $html, 'Sin polling: los números se calculan por trabajador.');
        $this->assertGreaterThanOrEqual(3, substr_count($html, '<canvas'), 'Mini-tendencias de trabajadores, lecciones y quizzes.');
    }

    public function test_stats_widget_handles_a_platform_without_data(): void
    {
        Livewire::actingAs($this->admin)->test(ResumenGeneral::class)
            ->assertSee('Sin altas en los últimos 7 días')
            ->assertSee('Aún no hay quizzes rendidos')
            ->assertSee('—');
    }

    public function test_bar_chart_has_one_gold_bar_per_active_area(): void
    {
        $this->escenario();

        $datos = $this->datosDelGrafico(AvancePorAreaChart::class);

        $this->assertSame('bar', $this->tipoDelGrafico(AvancePorAreaChart::class));
        $this->assertSame(['Ventas', 'Seguridad'], $datos['labels']);
        $this->assertSame([77, 0], $datos['datasets'][0]['data']);
        $this->assertSame('#D7A743', $datos['datasets'][0]['backgroundColor']);
        $this->assertSame('#0E1A34', $datos['datasets'][0]['borderColor']);
    }

    public function test_doughnut_chart_splits_workers_by_state_with_brand_colors(): void
    {
        $this->escenario();

        $datos = $this->datosDelGrafico(EstadoTrabajadoresChart::class);

        $this->assertSame('doughnut', $this->tipoDelGrafico(EstadoTrabajadoresChart::class));
        $this->assertSame(['Completado', 'En progreso', 'Sin iniciar'], $datos['labels']);
        $this->assertSame([1, 2, 1], $datos['datasets'][0]['data']);
        $this->assertSame(['#D7A743', '#4A6FA5', '#B3B3B3'], $datos['datasets'][0]['backgroundColor']);
    }

    public function test_charts_render_without_polling(): void
    {
        $this->escenario();

        $titulos = [AvancePorAreaChart::class => 'Avance promedio por área', EstadoTrabajadoresChart::class => 'Trabajadores por estado'];

        foreach ($titulos as $grafico => $titulo) {
            $componente = Livewire::actingAs($this->admin)->test($grafico)->assertSee($titulo);

            $this->assertStringNotContainsString('wire:poll', $componente->html(), $grafico);
        }
    }

    public function test_workers_table_lists_active_workers_with_progress_state_and_last_activity(): void
    {
        $datos = $this->escenario();

        Livewire::actingAs($this->admin)->test(TrabajadoresAvance::class)
            ->assertSee('Trabajadores y su avance')
            ->assertCanSeeTableRecords([$datos['ana'], $datos['luis'], $datos['maria'], $datos['carla']])
            ->assertCanNotSeeTableRecords([$datos['pedro'], $this->admin])
            ->assertTableColumnStateSet('avance', 100, $datos['maria'])
            ->assertTableColumnStateSet('avance', 50, $datos['ana'])
            ->assertTableColumnStateSet('avance', 0, $datos['carla'])
            ->assertTableColumnStateSet('areas_count', 2, $datos['ana'])
            ->assertTableColumnFormattedStateSet('estado', 'Completado', $datos['maria'])
            ->assertTableColumnFormattedStateSet('estado', 'En progreso', $datos['luis'])
            ->assertTableColumnFormattedStateSet('estado', 'Sin iniciar', $datos['carla'])
            ->assertTableColumnStateSet('ultimo_avance', null, $datos['carla'])
            ->assertTableActionHasUrl('pdf', route('reportes.trabajador', $datos['ana']), $datos['ana']);
    }

    public function test_workers_table_can_be_searched(): void
    {
        $datos = $this->escenario();

        Livewire::actingAs($this->admin)->test(TrabajadoresAvance::class)
            ->searchTable('Maria')
            ->assertCanSeeTableRecords([$datos['maria']])
            ->assertCanNotSeeTableRecords([$datos['ana'], $datos['luis']]);
    }

    public function test_workers_table_has_a_friendly_empty_state(): void
    {
        Livewire::actingAs($this->admin)->test(TrabajadoresAvance::class)
            ->assertSee('Aún no hay trabajadores activos');
    }

    /**
     * @param  class-string  $widget
     * @return array<string, mixed>
     */
    private function datosDelGrafico(string $widget): array
    {
        $metodo = new ReflectionMethod($widget, 'getData');

        return $metodo->invoke(app($widget));
    }

    /**
     * @param  class-string  $widget
     */
    private function tipoDelGrafico(string $widget): string
    {
        return (new ReflectionMethod($widget, 'getType'))->invoke(app($widget));
    }
}
