<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Feedback;
use App\Models\IntentoQuiz;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Quiz;
use App\Models\User;
use App\Services\RutaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RutaAreaTest extends TestCase
{
    use RefreshDatabase;

    private User $trabajador;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trabajador = User::factory()->create(['rol' => 'trabajador']);
        $this->area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);
        $this->trabajador->areas()->attach($this->area);
    }

    private function modulo(string $titulo, array $atributos = [], ?Area $area = null): Modulo
    {
        return Modulo::create($atributos + ['area_id' => ($area ?? $this->area)->id, 'titulo' => $titulo]);
    }

    private function leccion(Modulo $modulo, string $titulo, int $orden, bool $vista = false, array $atributos = []): Leccion
    {
        $leccion = Leccion::create($atributos + ['modulo_id' => $modulo->id, 'titulo' => $titulo, 'orden' => $orden, 'activa' => true]);

        if ($vista) {
            $this->trabajador->lecciones()->attach($leccion);
        }

        return $leccion;
    }

    private function quiz(Modulo $modulo, bool $conPreguntas = true): Quiz
    {
        $quiz = Quiz::create(['modulo_id' => $modulo->id, 'titulo' => 'Quiz '.$modulo->titulo]);

        if ($conPreguntas) {
            $pregunta = Pregunta::create(['quiz_id' => $quiz->id, 'enunciado' => 'Pregunta', 'orden' => 1]);
            Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'Correcta', 'es_correcta' => true]);
            Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'Incorrecta', 'es_correcta' => false]);
        }

        return $quiz;
    }

    private function aprobar(Quiz $quiz): void
    {
        IntentoQuiz::factory()->create(['user_id' => $this->trabajador->id, 'quiz_id' => $quiz->id, 'puntaje' => 90, 'aprobado' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function ruta(bool $accesoTotal = false, ?string $encuesta = null, ?User $usuario = null): array
    {
        return app(RutaService::class)->paraArea($usuario ?? $this->trabajador, $this->area, $accesoTotal, $encuesta);
    }

    /**
     * @param  array<string, mixed>  $ruta
     * @return list<string>
     */
    private function estados(array $ruta, int $tramo): array
    {
        return $ruta['modulos'][$tramo]['nodos']->pluck('estado')->all();
    }

    /**
     * Módulo 1 con 3 lecciones (la primera vista) y quiz; módulo 2 con una lección.
     *
     * @return array{0: Modulo, 1: Modulo, 2: Quiz, 3: array<int, Leccion>}
     */
    private function escenario(): array
    {
        $uno = $this->modulo('Modulo uno', ['orden' => 1]);
        $lecciones = [
            $this->leccion($uno, 'Leccion 1', 1, vista: true),
            $this->leccion($uno, 'Leccion 2', 2),
            $this->leccion($uno, 'Leccion 3', 3),
        ];
        $quiz = $this->quiz($uno);
        $dos = $this->modulo('Modulo dos', ['orden' => 2]);
        $lecciones[] = $this->leccion($dos, 'Leccion 4', 1);

        return [$uno, $dos, $quiz, $lecciones];
    }

    // ------------------------------------------------------------- servicio

    public function test_nodes_follow_the_real_progress_with_a_single_current_step(): void
    {
        $this->escenario();

        $ruta = $this->ruta();

        $this->assertSame(['completado', 'actual', 'disponible', 'bloqueado'], $this->estados($ruta, 0));
        $this->assertSame(['bloqueado'], $this->estados($ruta, 1));
        $this->assertSame(['leccion', 'leccion', 'leccion', 'quiz'], $ruta['modulos'][0]['nodos']->pluck('tipo')->all());
        $this->assertSame('Leccion 2', $ruta['actual']['titulo']);

        $todos = $ruta['modulos']->flatMap(fn ($tramo) => $tramo['nodos']);
        $this->assertCount(1, $todos->where('estado', 'actual'));
    }

    public function test_the_quiz_becomes_current_once_all_lessons_are_seen(): void
    {
        [, , , $lecciones] = $this->escenario();
        $this->trabajador->lecciones()->attach([$lecciones[1]->id, $lecciones[2]->id]);

        $ruta = $this->ruta();

        $this->assertSame(['completado', 'completado', 'completado', 'actual'], $this->estados($ruta, 0));
        $this->assertSame('quiz', $ruta['actual']['tipo']);
        $this->assertSame(['bloqueado'], $this->estados($ruta, 1), 'El módulo 2 sigue bloqueado hasta aprobar el quiz.');
    }

    public function test_approving_the_quiz_completes_the_module_and_opens_the_next_one(): void
    {
        [, $dos, $quiz, $lecciones] = $this->escenario();
        $this->trabajador->lecciones()->attach([$lecciones[1]->id, $lecciones[2]->id]);
        $this->aprobar($quiz);
        $this->leccion($dos, 'Leccion 5', 2);

        $ruta = $this->ruta();

        $this->assertSame(['completado', 'completado', 'completado', 'completado'], $this->estados($ruta, 0));
        $this->assertSame(['actual', 'disponible'], $this->estados($ruta, 1));
        $this->assertSame('Leccion 4', $ruta['actual']['titulo']);
        $this->assertSame('completado', $ruta['modulos'][0]['estado']);
    }

    public function test_locked_nodes_have_no_link_and_the_others_do(): void
    {
        [, , , $lecciones] = $this->escenario();

        $ruta = $this->ruta();
        $nodosUno = $ruta['modulos'][0]['nodos'];

        $this->assertSame(route('lecciones.show', $lecciones[0]), $nodosUno[0]['url']);
        $this->assertSame(route('lecciones.show', $lecciones[1]), $nodosUno[1]['url']);
        $this->assertSame(route('lecciones.show', $lecciones[2]), $nodosUno[2]['url']);
        $this->assertNull($nodosUno[3]['url'], 'El quiz está bloqueado mientras falten lecciones.');
        $this->assertNull($ruta['modulos'][1]['nodos'][0]['url']);
    }

    public function test_admins_can_open_locked_nodes(): void
    {
        $this->escenario();
        $admin = User::factory()->create(['rol' => 'admin']);

        $ruta = $this->ruta(accesoTotal: true, usuario: $admin);

        $this->assertSame('bloqueado', $ruta['modulos'][1]['nodos'][0]['estado']);
        $this->assertNotNull($ruta['modulos'][1]['nodos'][0]['url']);
    }

    public function test_inactive_content_empty_quizzes_and_modules_without_quiz_are_handled(): void
    {
        $modulo = $this->modulo('Con ocultos', ['orden' => 1]);
        $this->leccion($modulo, 'Visible', 1, vista: true);
        $this->leccion($modulo, 'Oculta', 2, atributos: ['activa' => false]);
        $this->quiz($modulo, conPreguntas: false);
        $inactivo = $this->modulo('Inactivo', ['orden' => 2, 'activo' => false]);
        $this->leccion($inactivo, 'De modulo inactivo', 1);

        $ruta = $this->ruta();

        $this->assertCount(1, $ruta['modulos']);
        $this->assertSame(['leccion'], $ruta['modulos'][0]['nodos']->pluck('tipo')->all());
        $this->assertSame(['completado'], $this->estados($ruta, 0));
        $this->assertNull($ruta['actual']);
    }

    public function test_an_empty_module_does_not_block_the_route(): void
    {
        $this->modulo('Vacio', ['orden' => 1]);
        $real = $this->modulo('Real', ['orden' => 2]);
        $this->leccion($real, 'Primera', 1);

        $ruta = $this->ruta();

        $this->assertTrue($ruta['modulos'][0]['nodos']->isEmpty());
        $this->assertSame(['actual'], $this->estados($ruta, 1));
    }

    public function test_a_module_with_only_a_quiz_is_reachable(): void
    {
        $soloQuiz = $this->modulo('Solo quiz');
        $this->quiz($soloQuiz);

        $ruta = $this->ruta();

        $this->assertSame(['quiz'], $ruta['modulos'][0]['nodos']->pluck('tipo')->all());
        $this->assertSame(['actual'], $this->estados($ruta, 0));
        $this->assertNotNull($ruta['actual']['url']);
    }

    public function test_the_survey_is_the_last_node_when_the_area_is_completed(): void
    {
        $modulo = $this->modulo('Unico');
        $this->leccion($modulo, 'Leccion', 1, vista: true);

        $this->assertNull($this->ruta()['encuesta']);

        $pendiente = $this->ruta(encuesta: 'pendiente');
        $this->assertSame('actual', $pendiente['encuesta']['estado']);
        $this->assertSame(route('areas.encuesta', $this->area), $pendiente['encuesta']['url']);
        $this->assertSame($pendiente['encuesta'], $pendiente['actual']);

        $respondida = $this->ruta(encuesta: 'respondida');
        $this->assertSame('completado', $respondida['encuesta']['estado']);
        $this->assertNull($respondida['encuesta']['url']);
        $this->assertNull($respondida['actual']);
    }

    // ----------------------------------------------------------------- vista

    public function test_list_mode_is_the_default_and_the_toggle_shows_both_options(): void
    {
        $this->escenario();

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', $this->area))
            ->assertOk()
            ->assertDontSee('data-ruta', false)
            ->assertSee('aria-label="Modo de vista"', false)
            ->assertSee('href="'.route('areas.show', ['area' => $this->area, 'vista' => 'ruta']).'"', false)
            ->assertSee('href="'.route('areas.show', ['area' => $this->area, 'vista' => 'lista']).'"', false)
            ->assertSee('Lista')
            ->assertSee('Ruta');
    }

    public function test_route_mode_is_remembered_in_the_session_and_can_be_switched_back(): void
    {
        $this->escenario();
        $this->actingAs($this->trabajador);

        $this->get(route('areas.show', ['area' => $this->area, 'vista' => 'ruta']))
            ->assertOk()
            ->assertSee('data-ruta', false);

        $this->get(route('areas.show', $this->area))->assertSee('data-ruta', false);

        $this->get(route('areas.show', ['area' => $this->area, 'vista' => 'zzz']))->assertSee('data-ruta', false);

        $this->get(route('areas.show', ['area' => $this->area, 'vista' => 'lista']))->assertDontSee('data-ruta', false);
        $this->get(route('areas.show', $this->area))->assertDontSee('data-ruta', false);
    }

    public function test_the_preference_applies_to_every_area(): void
    {
        $otra = Area::create(['nombre' => 'Seguridad', 'slug' => 'seguridad']);
        $this->trabajador->areas()->attach($otra);
        $modulo = $this->modulo('Otro', area: $otra);
        $this->leccion($modulo, 'Leccion', 1);
        $this->actingAs($this->trabajador);

        $this->get(route('areas.show', ['area' => $this->area, 'vista' => 'ruta']));

        $this->get(route('areas.show', $otra))->assertSee('data-ruta', false);
    }

    public function test_route_markup_marks_completed_current_and_locked_nodes(): void
    {
        [, , , $lecciones] = $this->escenario();

        $respuesta = $this->actingAs($this->trabajador)
            ->get(route('areas.show', ['area' => $this->area, 'vista' => 'ruta']))
            ->assertOk()
            ->assertSee('data-estado="completado"', false)
            ->assertSee('data-estado="actual"', false)
            ->assertSee('data-estado="disponible"', false)
            ->assertSee('data-estado="bloqueado"', false)
            ->assertSee('bg-marca-dorado', false)
            ->assertSee('bg-marca-azul', false)
            ->assertSee('bg-marca-gris-claro', false)
            ->assertSee('Módulo 1')
            ->assertSee('Módulo 2')
            ->assertSee('1 de 3 lecciones')
            ->assertSee('Quiz del módulo');

        $html = $respuesta->getContent();

        $this->assertSame(1, substr_count($html, 'data-estado="actual"'));
        $this->assertSame(2, substr_count($html, 'data-estado="bloqueado"'), 'Quiz del módulo 1 y lección del módulo 2.');
        $this->assertStringContainsString('href="'.route('lecciones.show', $lecciones[0]).'"', $html);
        $this->assertStringContainsString('href="'.route('lecciones.show', $lecciones[1]).'"', $html);
        $this->assertStringNotContainsString('href="'.route('lecciones.show', $lecciones[3]).'"', $html);
    }

    public function test_the_current_node_has_a_continue_button_that_goes_to_it(): void
    {
        [, , , $lecciones] = $this->escenario();

        $html = $this->actingAs($this->trabajador)
            ->get(route('areas.show', ['area' => $this->area, 'vista' => 'ruta']))
            ->assertSee('Continuar')
            ->getContent();

        $this->assertSame(1, substr_count($html, 'Continuar'));
        $this->assertMatchesRegularExpression(
            '#<a href="'.preg_quote(route('lecciones.show', $lecciones[1]), '#').'"[^>]*>\s*Continuar#',
            $html
        );
    }

    public function test_locked_nodes_do_nothing_when_tapped_and_are_not_links(): void
    {
        [, , , $lecciones] = $this->escenario();

        $html = $this->actingAs($this->trabajador)
            ->get(route('areas.show', ['area' => $this->area, 'vista' => 'ruta']))
            ->getContent();

        $this->assertStringContainsString('cursor-not-allowed', $html);
        $this->assertStringNotContainsString('href="'.route('lecciones.show', $lecciones[3]).'"', $html);
        $this->assertStringNotContainsString('href="'.route('quiz.show', $lecciones[0]->modulo).'"', $html);
    }

    public function test_the_quiz_node_links_to_the_quiz_when_it_is_available(): void
    {
        [$uno, , , $lecciones] = $this->escenario();
        $this->trabajador->lecciones()->attach([$lecciones[1]->id, $lecciones[2]->id]);

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', ['area' => $this->area, 'vista' => 'ruta']))
            ->assertSee('href="'.route('quiz.show', $uno).'"', false)
            ->assertSee('data-nodo="quiz"', false);
    }

    public function test_admins_can_tap_locked_nodes_in_route_mode(): void
    {
        [, , , $lecciones] = $this->escenario();
        $admin = User::factory()->create(['rol' => 'admin']);

        $this->actingAs($admin)
            ->get(route('areas.show', ['area' => $this->area, 'vista' => 'ruta']))
            ->assertSee('href="'.route('lecciones.show', $lecciones[3]).'"', false);
    }

    public function test_the_survey_and_the_download_appear_in_route_mode_once_everything_is_done(): void
    {
        Storage::fake(config('filament.default_filesystem_disk'));
        $this->area->update(['resumen_pdf' => 'resumenes/area.pdf']);
        $modulo = $this->modulo('Unico');
        $this->leccion($modulo, 'Leccion', 1, vista: true);

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', ['area' => $this->area, 'vista' => 'ruta']))
            ->assertSee('data-nodo="encuesta"', false)
            ->assertSee('href="'.route('areas.encuesta', $this->area).'"', false)
            ->assertSee('Descargar resumen del área');

        Feedback::factory()->create(['user_id' => $this->trabajador->id, 'area_id' => $this->area->id]);

        $this->get(route('areas.show', ['area' => $this->area, 'vista' => 'ruta']))
            ->assertSee('data-nodo="encuesta"', false)
            ->assertDontSee('href="'.route('areas.encuesta', $this->area).'"', false);
    }

    public function test_the_survey_node_is_hidden_until_the_area_is_completed(): void
    {
        $this->escenario();

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', ['area' => $this->area, 'vista' => 'ruta']))
            ->assertDontSee('data-nodo="encuesta"', false)
            ->assertDontSee('Cuéntanos cómo te fue');
    }

    public function test_route_mode_has_no_pressure_mechanics(): void
    {
        $this->escenario();

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', ['area' => $this->area, 'vista' => 'ruta']))
            ->assertOk()
            ->assertDontSee('vidas', false)
            ->assertDontSee('racha', false)
            ->assertDontSee('liga', false)
            ->assertDontSee('XP', false);
    }

    public function test_route_mode_keeps_the_access_rules_of_the_area(): void
    {
        $ajena = Area::create(['nombre' => 'Ajena', 'slug' => 'ajena']);

        $this->get(route('areas.show', ['area' => $this->area, 'vista' => 'ruta']))->assertRedirect('/login');

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', ['area' => $ajena, 'vista' => 'ruta']))
            ->assertForbidden();
    }
}
