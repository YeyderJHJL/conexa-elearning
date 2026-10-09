<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\IntentoQuiz;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Quiz;
use App\Models\User;
use App\Services\ProgresoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProgresoQuizTest extends TestCase
{
    use RefreshDatabase;

    private User $trabajador;

    private Area $area;

    private ProgresoService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trabajador = User::factory()->create(['rol' => 'trabajador']);
        $this->area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);
        $this->trabajador->areas()->attach($this->area);
        $this->servicio = app(ProgresoService::class);
    }

    /**
     * Módulo con $lecciones lecciones activas (las primeras $vistas ya vistas) y, opcionalmente, un quiz con una pregunta.
     */
    private function modulo(int $lecciones, int $vistas = 0, bool $conQuiz = false, ?Area $area = null): Modulo
    {
        $modulo = Modulo::create(['area_id' => ($area ?? $this->area)->id, 'titulo' => 'Modulo '.fake()->unique()->word()]);

        for ($i = 0; $i < $lecciones; $i++) {
            $leccion = Leccion::create(['modulo_id' => $modulo->id, 'titulo' => "Leccion {$i}", 'orden' => $i, 'activa' => true]);

            if ($i < $vistas) {
                $this->trabajador->lecciones()->attach($leccion);
            }
        }

        if ($conQuiz) {
            $quiz = Quiz::create(['modulo_id' => $modulo->id, 'titulo' => 'Quiz del modulo']);
            $pregunta = Pregunta::create(['quiz_id' => $quiz->id, 'enunciado' => 'Pregunta', 'orden' => 1]);
            Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'Correcta', 'es_correcta' => true]);
            Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'Incorrecta', 'es_correcta' => false]);
        }

        return $modulo;
    }

    private function intento(Modulo $modulo, int $puntaje, bool $aprobado, ?User $usuario = null): IntentoQuiz
    {
        return IntentoQuiz::factory()->create([
            'user_id' => ($usuario ?? $this->trabajador)->id,
            'quiz_id' => $modulo->quiz->id,
            'puntaje' => $puntaje,
            'aprobado' => $aprobado,
        ]);
    }

    public function test_all_lessons_seen_without_an_approved_quiz_is_not_complete(): void
    {
        $conQuiz = $this->modulo(4, 4, conQuiz: true);
        $siguiente = $this->modulo(2);

        $this->assertSame(80, $this->servicio->modulo($this->trabajador, $conQuiz));
        $this->assertSame(
            [$conQuiz->id => 'en_curso', $siguiente->id => 'bloqueado'],
            $this->servicio->estados($this->trabajador, $this->area)->all()
        );
        $this->assertFalse($this->servicio->moduloDesbloqueado($this->trabajador, $siguiente));
    }

    public function test_approving_the_quiz_completes_the_module_and_unlocks_the_next_one(): void
    {
        $conQuiz = $this->modulo(4, 4, conQuiz: true);
        $siguiente = $this->modulo(2);
        $this->intento($conQuiz, 90, true);

        $this->assertSame(100, $this->servicio->modulo($this->trabajador, $conQuiz));
        $this->assertSame(
            [$conQuiz->id => 'completado', $siguiente->id => 'en_curso'],
            $this->servicio->estados($this->trabajador, $this->area)->all()
        );
        $this->assertTrue($this->servicio->moduloDesbloqueado($this->trabajador, $siguiente));
    }

    public function test_failed_attempts_do_not_count_but_one_approved_attempt_among_them_does(): void
    {
        $conQuiz = $this->modulo(2, 2, conQuiz: true);
        $this->intento($conQuiz, 30, false);
        $this->intento($conQuiz, 50, false);

        $this->assertSame(66, $this->servicio->modulo($this->trabajador, $conQuiz));

        $this->intento($conQuiz, 80, true);
        $this->intento($conQuiz, 40, false);

        $this->assertSame(100, $this->servicio->modulo($this->trabajador, $conQuiz));
    }

    public function test_approved_attempts_of_other_users_do_not_count(): void
    {
        $conQuiz = $this->modulo(2, 2, conQuiz: true);
        $otro = User::factory()->create(['rol' => 'trabajador']);
        $this->intento($conQuiz, 100, true, $otro);

        $this->assertSame(66, $this->servicio->modulo($this->trabajador, $conQuiz));
        $this->assertSame(33, $this->servicio->modulo($otro, $conQuiz));
    }

    public function test_an_approved_quiz_does_not_replace_unseen_lessons(): void
    {
        $conQuiz = $this->modulo(2, 1, conQuiz: true);
        $this->intento($conQuiz, 100, true);

        $this->assertSame(66, $this->servicio->modulo($this->trabajador, $conQuiz));
        $this->assertSame('en_curso', $this->servicio->estados($this->trabajador, $this->area)->first());
    }

    public function test_a_quiz_without_questions_is_ignored(): void
    {
        $modulo = $this->modulo(2, 2);
        Quiz::create(['modulo_id' => $modulo->id, 'titulo' => 'Quiz vacio']);

        $this->assertSame(100, $this->servicio->modulo($this->trabajador, $modulo));
        $this->assertSame('completado', $this->servicio->estados($this->trabajador, $this->area)->first());
    }

    public function test_a_module_with_only_a_quiz_completes_when_the_quiz_is_approved(): void
    {
        $soloQuiz = $this->modulo(0, conQuiz: true);
        $siguiente = $this->modulo(1);

        $this->assertTrue($this->servicio->leccionesCompletadas($this->trabajador, $soloQuiz));
        $this->assertSame(0, $this->servicio->modulo($this->trabajador, $soloQuiz));
        $this->assertFalse($this->servicio->moduloDesbloqueado($this->trabajador, $siguiente));

        $this->intento($soloQuiz, 100, true);

        $this->assertSame(100, $this->servicio->modulo($this->trabajador, $soloQuiz));
        $this->assertTrue($this->servicio->moduloDesbloqueado($this->trabajador, $siguiente));
    }

    public function test_area_and_global_progress_reflect_the_quiz(): void
    {
        $conQuiz = $this->modulo(2, 2, conQuiz: true);
        $this->modulo(2, 0);

        $this->assertSame(33, $this->servicio->area($this->trabajador, $this->area));
        $this->assertSame(33, $this->servicio->global($this->trabajador));

        $this->intento($conQuiz, 100, true);

        $this->assertSame(50, $this->servicio->area($this->trabajador, $this->area));
        $this->assertSame(50, $this->servicio->global($this->trabajador));
    }

    public function test_summary_still_uses_a_single_query_with_quizzes(): void
    {
        $areas = collect(['A', 'B', 'C'])->map(function (string $nombre) {
            $area = Area::create(['nombre' => $nombre, 'slug' => $nombre]);
            $this->trabajador->areas()->attach($area);
            $this->intento($this->modulo(2, 2, conQuiz: true, area: $area), 90, true);

            return $area;
        });

        DB::enableQueryLog();
        $resumen = $this->servicio->resumen($this->trabajador, $areas);

        $this->assertCount(1, DB::getQueryLog());
        $this->assertSame(100, $resumen['global']);
    }

    public function test_next_step_points_to_the_pending_quiz_once_the_lessons_are_seen(): void
    {
        $modulo = $this->modulo(2, 2, conQuiz: true);

        $paso = $this->servicio->siguientePaso($this->trabajador);

        $this->assertSame('quiz', $paso['tipo']);
        $this->assertSame($modulo->id, $paso['modulo']->id);

        $this->intento($modulo, 100, true);

        $this->assertNull($this->servicio->siguientePaso($this->trabajador));
    }

    public function test_dashboard_continue_button_leads_to_the_pending_quiz(): void
    {
        $modulo = $this->modulo(1, 1, conQuiz: true);

        $this->actingAs($this->trabajador)
            ->get('/dashboard')
            ->assertSee('Continuar donde quedaste')
            ->assertSee('Rendir el quiz: Quiz del modulo')
            ->assertSee(route('quiz.show', $modulo), false);
    }

    public function test_area_detail_does_not_mark_a_module_completed_until_the_quiz_is_approved(): void
    {
        $modulo = $this->modulo(4, 4, conQuiz: true);

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', $this->area))
            ->assertSee('80%')
            ->assertSee('En curso')
            ->assertDontSee('Completado');

        $this->intento($modulo, 100, true);

        $this->get(route('areas.show', $this->area))
            ->assertSee('100%')
            ->assertSee('Completado');
    }

    public function test_module_page_shows_the_quiz_as_not_taken(): void
    {
        $modulo = $this->modulo(1, 1, conQuiz: true);

        $this->actingAs($this->trabajador)
            ->get(route('modulos.show', $modulo))
            ->assertOk()
            ->assertSee('No rendido')
            ->assertSee('Aún no has rendido este quiz.')
            ->assertSee('Rendir quiz del módulo')
            ->assertSee('Nota mínima: 70%')
            ->assertSee(route('quiz.show', $modulo), false)
            ->assertDontSee('Ver mi último resultado');
    }

    public function test_module_page_shows_the_last_failed_attempt_with_its_score(): void
    {
        $modulo = $this->modulo(1, 1, conQuiz: true);
        $this->intento($modulo, 20, false);
        $ultimo = $this->intento($modulo, 55, false);

        $this->actingAs($this->trabajador)
            ->get(route('modulos.show', $modulo))
            ->assertSee('No aprobado')
            ->assertSee('Último intento:')
            ->assertSee('55%')
            ->assertSee('Reintentar quiz del módulo')
            ->assertSee(route('quiz.resultado', [$modulo, $ultimo]), false);
    }

    public function test_module_page_shows_the_approved_quiz_even_after_a_later_failed_attempt(): void
    {
        $modulo = $this->modulo(1, 1, conQuiz: true);
        $aprobado = $this->intento($modulo, 85, true);
        $this->intento($modulo, 40, false);

        $this->actingAs($this->trabajador)
            ->get(route('modulos.show', $modulo))
            ->assertSee('Aprobaste con')
            ->assertSee('85%')
            ->assertSee('2 intentos')
            ->assertSee('Volver a rendir el quiz')
            ->assertSee(route('quiz.resultado', [$modulo, $aprobado]), false);
    }

    public function test_quiz_access_stays_disabled_until_the_lessons_are_finished(): void
    {
        $modulo = $this->modulo(2, 1, conQuiz: true);
        $this->intento($modulo, 40, false);

        $this->actingAs($this->trabajador)
            ->get(route('modulos.show', $modulo))
            ->assertSee('Completa todas las lecciones para desbloquear el quiz.')
            ->assertDontSee('href="'.route('quiz.show', $modulo).'"', false);
    }
}
