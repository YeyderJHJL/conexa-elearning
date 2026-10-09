<?php

namespace Tests\Feature;

use App\Http\Controllers\ReporteController;
use App\Models\Area;
use App\Models\IntentoQuiz;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Quiz;
use App\Models\User;
use App\Services\ReporteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReporteTest extends TestCase
{
    use RefreshDatabase;

    private User $trabajador;

    private Area $area;

    private ReporteService $reporte;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trabajador = User::factory()->create([
            'name' => 'Ana Torres',
            'rol' => 'trabajador',
            'cargo' => 'Vendedora',
            'fecha_ingreso' => '2026-09-01',
        ]);
        $this->area = $this->area('Ventas');
        $this->reporte = app(ReporteService::class);
    }

    private function area(string $nombre, bool $asignada = true, bool $activa = true): Area
    {
        $area = Area::create(['nombre' => $nombre, 'slug' => str($nombre)->slug(), 'activa' => $activa]);

        if ($asignada) {
            $this->trabajador->areas()->attach($area);
        }

        return $area;
    }

    private function modulo(string $titulo, int $lecciones, int $vistas = 0, bool $conQuiz = false, ?Area $area = null): Modulo
    {
        $modulo = Modulo::create(['area_id' => ($area ?? $this->area)->id, 'titulo' => $titulo]);

        for ($i = 0; $i < $lecciones; $i++) {
            $leccion = Leccion::create(['modulo_id' => $modulo->id, 'titulo' => "Leccion {$i}", 'orden' => $i, 'activa' => true]);

            if ($i < $vistas) {
                $this->trabajador->lecciones()->attach($leccion);
            }
        }

        if ($conQuiz) {
            $quiz = Quiz::create(['modulo_id' => $modulo->id, 'titulo' => "Quiz de {$titulo}"]);
            $pregunta = Pregunta::create(['quiz_id' => $quiz->id, 'enunciado' => 'Pregunta', 'orden' => 1]);
            Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'Correcta', 'es_correcta' => true]);
            Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'Incorrecta', 'es_correcta' => false]);
        }

        return $modulo;
    }

    private function intento(Modulo $modulo, int $puntaje, ?User $usuario = null): IntentoQuiz
    {
        return IntentoQuiz::factory()->create([
            'user_id' => ($usuario ?? $this->trabajador)->id,
            'quiz_id' => $modulo->quiz->id,
            'puntaje' => $puntaje,
            'aprobado' => $puntaje >= 70,
        ]);
    }

    public function test_workers_have_no_access_to_the_general_report(): void
    {
        $this->modulo('Introduccion', 1);

        $this->actingAs($this->trabajador)->get('/reporte')->assertNotFound();
        $this->assertFalse(Route::has('reporte.descargar'));
    }

    public function test_reserved_controller_builds_the_pdf_of_the_given_worker(): void
    {
        // El controlador no tiene ruta (se conectará en el panel de admin): se registra una solo para esta prueba.
        Route::middleware('web')->get('/_prueba/reporte/{usuario}', ReporteController::class);
        $this->modulo('Introduccion', 2, 1, conQuiz: true);

        $respuesta = $this->get("/_prueba/reporte/{$this->trabajador->id}")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('reporte-capacitacion-ana-torres-'.now()->format('Ymd').'.pdf');

        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
    }

    public function test_reserved_controller_works_for_a_worker_without_areas(): void
    {
        Route::middleware('web')->get('/_prueba/reporte/{usuario}', ReporteController::class);
        $sinAreas = User::factory()->create(['rol' => 'trabajador']);

        $this->get("/_prueba/reporte/{$sinAreas->id}")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_report_data_comes_from_the_database_only_for_assigned_active_areas(): void
    {
        $m1 = $this->modulo('Intro', 2, 2, conQuiz: true);
        $m2 = $this->modulo('Avanzado', 1, 0);
        $this->intento($m1, 40);
        $this->intento($m1, 60);
        $this->modulo('Modulo de area inactiva', 1, 0, area: $this->area('Inactiva', activa: false));
        $this->modulo('Modulo de area ajena', 1, 0, area: $this->area('Ajena', asignada: false));

        $datos = $this->reporte->datosPara($this->trabajador);

        $this->assertSame(['Ventas'], $datos['areas']->map(fn ($fila) => $fila['area']->nombre)->all());
        $this->assertSame(33, $datos['global']);
        $this->assertSame(33, $datos['areas']->first()['avance']);
        $this->assertSame('En progreso', $datos['estadoFinal']);

        [$fila1, $fila2] = $datos['areas']->first()['modulos']->all();
        $this->assertSame($m1->id, $fila1['modulo']->id);
        $this->assertSame('en_curso', $fila1['estado']);
        $this->assertTrue($fila1['tieneQuiz']);
        $this->assertSame(2, $fila1['intentos']);
        $this->assertSame(60, $fila1['mejorNota']);
        $this->assertFalse($fila1['aprobado']);
        $this->assertSame($m2->id, $fila2['modulo']->id);
        $this->assertSame('bloqueado', $fila2['estado']);
        $this->assertFalse($fila2['tieneQuiz']);
        $this->assertSame(0, $fila2['intentos']);
        $this->assertNull($fila2['mejorNota']);
    }

    public function test_attempts_of_other_users_are_not_included(): void
    {
        $modulo = $this->modulo('Intro', 1, 1, conQuiz: true);
        $otro = User::factory()->create(['rol' => 'trabajador']);
        $this->intento($modulo, 95, $otro);

        $fila = $this->reporte->datosPara($this->trabajador)['areas']->first()['modulos']->first();

        $this->assertSame(0, $fila['intentos']);
        $this->assertNull($fila['mejorNota']);
    }

    public function test_points_to_reinforce_are_attempted_unapproved_quizzes_from_lowest_to_highest_score(): void
    {
        $bajo = $this->modulo('Bajo', 1, 1, conQuiz: true);
        $medio = $this->modulo('Medio', 1, 1, conQuiz: true);
        $aprobado = $this->modulo('Aprobado', 1, 1, conQuiz: true);
        $sinRendir = $this->modulo('Sin rendir', 1, 1, conQuiz: true);
        $sinQuiz = $this->modulo('Sin quiz', 1, 1);
        $this->intento($medio, 55);
        $this->intento($bajo, 20);
        $this->intento($bajo, 35);
        $this->intento($aprobado, 40);
        $this->intento($aprobado, 90);

        $reforzar = $this->reporte->datosPara($this->trabajador)['reforzar'];

        $this->assertSame([$bajo->id, $medio->id], $reforzar->map(fn ($punto) => $punto['modulo']->id)->all());
        $this->assertSame([35, 55], $reforzar->pluck('mejorNota')->all());
        $this->assertSame($this->area->id, $reforzar->first()['area']->id);
        $this->assertNotContains($sinRendir->id, $reforzar->map(fn ($punto) => $punto['modulo']->id)->all());
        $this->assertNotContains($sinQuiz->id, $reforzar->map(fn ($punto) => $punto['modulo']->id)->all());
    }

    public function test_final_status_is_completed_only_when_everything_is_done(): void
    {
        $modulo = $this->modulo('Intro', 2, 2, conQuiz: true);

        $this->assertSame('En progreso', $this->reporte->datosPara($this->trabajador)['estadoFinal']);

        $this->intento($modulo, 100);
        $datos = $this->reporte->datosPara($this->trabajador);

        $this->assertSame('Completado', $datos['estadoFinal']);
        $this->assertSame(100, $datos['global']);
    }

    public function test_lessons_added_after_approval_are_reported_separately(): void
    {
        $this->travelTo(now()->subDays(10));
        $modulo = $this->modulo('Intro', 2, 2, conQuiz: true);

        $this->travelTo(now()->addDays(2));
        $aprobadoEn = now();
        $this->intento($modulo, 90);

        $this->travelTo(now()->addDays(3));
        $vista = Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'Nueva vista', 'activa' => true]);
        $this->trabajador->lecciones()->attach($vista);
        Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'Nueva pendiente', 'activa' => true]);
        Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'Nueva oculta', 'activa' => false]);

        $notas = $this->reporte->datosPara($this->trabajador)['leccionesNuevas'];

        $this->assertCount(1, $notas);
        $this->assertSame($modulo->id, $notas->first()['modulo']->id);
        $this->assertSame(2, $notas->first()['nuevas']);
        $this->assertSame(1, $notas->first()['pendientes']);
        $this->assertSame($aprobadoEn->toDateString(), $notas->first()['aprobadoEn']->toDateString());
    }

    public function test_no_lesson_note_without_lessons_added_after_approval_or_without_a_quiz(): void
    {
        $modulo = $this->modulo('Intro', 2, 2, conQuiz: true);
        $this->modulo('Sin quiz', 1, 1);
        $this->travelTo(now()->addMinute());
        $this->intento($modulo, 90);

        $this->assertTrue($this->reporte->datosPara($this->trabajador)['leccionesNuevas']->isEmpty());

        $this->travelTo(now()->addDay());
        Leccion::create(['modulo_id' => Modulo::where('titulo', 'Sin quiz')->first()->id, 'titulo' => 'Nueva', 'activa' => true]);

        $this->assertTrue($this->reporte->datosPara($this->trabajador)['leccionesNuevas']->isEmpty());
    }

    public function test_report_view_shows_worker_summary_areas_modules_and_points_to_reinforce(): void
    {
        $modulo = $this->modulo('Introduccion a ventas', 2, 2, conQuiz: true);
        $this->intento($modulo, 60);
        $this->modulo('Cierre de ventas', 1, 0);

        $html = view('pdf.reporte', $this->reporte->datosPara($this->trabajador))->render();

        foreach ([
            'Reporte de capacitación', 'Ana Torres', 'Vendedora', '01/09/2026', 'Avance global', '33%', 'En progreso',
            'Ventas', 'Introduccion a ventas', 'Cierre de ventas', '60%', 'No aprobado', 'Bloqueado', 'Sin quiz',
            'Puntos a reforzar', 'Logo Conexa',
        ] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }

        $this->assertStringNotContainsString('Contenido agregado después de aprobar', $html);
        $this->assertStringNotContainsString('src=', $html);
    }

    public function test_report_view_includes_the_lesson_note_when_there_is_one(): void
    {
        $this->travelTo(now()->subDays(5));
        $modulo = $this->modulo('Intro', 1, 1, conQuiz: true);
        $this->travelTo(now()->addDay());
        $this->intento($modulo, 100);
        $this->travelTo(now()->addDay());
        Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'Nueva', 'activa' => true]);

        $html = view('pdf.reporte', $this->reporte->datosPara($this->trabajador))->render();

        $this->assertStringContainsString('Contenido agregado después de aprobar', $html);
        $this->assertStringContainsString('se agregó 1 lección', $html);
        $this->assertStringContainsString('Queda 1 pendiente', $html);
    }

    public function test_dashboard_no_longer_offers_the_general_report(): void
    {
        $this->modulo('Intro', 1);

        $this->actingAs($this->trabajador)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Descargar mi reporte');
    }
}
