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
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    private User $trabajador;

    private Area $area;

    private Modulo $modulo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trabajador = User::factory()->create(['rol' => 'trabajador']);
        $this->area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);
        $this->trabajador->areas()->attach($this->area);
        $this->modulo = $this->moduloConLeccion($this->area, completada: true);
    }

    private function moduloConLeccion(Area $area, bool $completada, int $orden = 0): Modulo
    {
        $modulo = Modulo::create(['area_id' => $area->id, 'titulo' => 'Modulo '.fake()->unique()->word(), 'orden' => $orden]);
        $leccion = Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'Leccion', 'activa' => true]);

        if ($completada) {
            $this->trabajador->lecciones()->attach($leccion);
        }

        return $modulo;
    }

    /**
     * Quiz con $preguntas preguntas de 3 opciones (una correcta).
     */
    private function quiz(?Modulo $modulo = null, int $preguntas = 3, ?int $notaMinima = null): Quiz
    {
        $quiz = Quiz::create(['modulo_id' => ($modulo ?? $this->modulo)->id, 'titulo' => 'Quiz de prueba', 'nota_minima' => $notaMinima]);

        for ($i = 1; $i <= $preguntas; $i++) {
            $pregunta = Pregunta::create(['quiz_id' => $quiz->id, 'enunciado' => "Pregunta {$i}", 'orden' => $i]);
            Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => "Correcta {$i}", 'es_correcta' => true]);
            Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => "Incorrecta A{$i}", 'es_correcta' => false]);
            Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => "Incorrecta B{$i}", 'es_correcta' => false]);
        }

        return $quiz;
    }

    /**
     * Respuestas en las que las primeras $aciertos preguntas se contestan bien y el resto mal.
     *
     * @return array<int, int>
     */
    private function respuestas(Quiz $quiz, int $aciertos): array
    {
        return $quiz->preguntas()->with('opciones')->get()->values()
            ->mapWithKeys(fn (Pregunta $pregunta, int $i) => [
                $pregunta->id => $pregunta->opciones->firstWhere('es_correcta', $i < $aciertos)->id,
            ])
            ->all();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->quiz();

        $this->get(route('quiz.show', $this->modulo))->assertRedirect('/login');
        $this->post(route('quiz.enviar', $this->modulo))->assertRedirect('/login');
    }

    public function test_worker_cannot_use_the_quiz_of_an_unassigned_area(): void
    {
        $ajena = Area::create(['nombre' => 'Seguridad', 'slug' => 'seguridad']);
        $moduloAjeno = $this->moduloConLeccion($ajena, completada: true);
        $quizAjeno = $this->quiz($moduloAjeno);
        $intento = IntentoQuiz::factory()->create(['quiz_id' => $quizAjeno->id]);

        $this->actingAs($this->trabajador);

        $this->get(route('quiz.show', $moduloAjeno))->assertForbidden();
        $this->post(route('quiz.enviar', $moduloAjeno), ['respuestas' => $this->respuestas($quizAjeno, 3)])->assertForbidden();
        $this->get(route('quiz.resultado', [$moduloAjeno, $intento]))->assertForbidden();
        $this->assertDatabaseCount('intentos_quiz', 1);
    }

    public function test_quiz_requires_all_lessons_completed(): void
    {
        $pendiente = $this->moduloConLeccion($this->area, completada: false);
        $quiz = $this->quiz($pendiente);

        $this->actingAs($this->trabajador)
            ->get(route('quiz.show', $pendiente))
            ->assertRedirect(route('modulos.show', $pendiente))
            ->assertSessionHas('aviso');

        $this->post(route('quiz.enviar', $pendiente), ['respuestas' => $this->respuestas($quiz, 3)])
            ->assertRedirect(route('modulos.show', $pendiente));

        $this->assertDatabaseCount('intentos_quiz', 0);
    }

    public function test_module_without_quiz_or_without_questions_redirects_with_a_notice(): void
    {
        $this->actingAs($this->trabajador)
            ->get(route('quiz.show', $this->modulo))
            ->assertRedirect(route('modulos.show', $this->modulo))
            ->assertSessionHas('aviso');

        Quiz::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Vacio']);

        $this->get(route('quiz.show', $this->modulo))
            ->assertRedirect(route('modulos.show', $this->modulo))
            ->assertSessionHas('aviso');
    }

    public function test_quiz_of_a_module_locked_by_the_sequence_redirects_to_the_area(): void
    {
        $this->modulo->update(['orden' => 1]);
        $this->trabajador->lecciones()->detach();
        $bloqueado = $this->moduloConLeccion($this->area, completada: true, orden: 2);
        $this->quiz($bloqueado);

        $this->actingAs($this->trabajador)
            ->get(route('quiz.show', $bloqueado))
            ->assertRedirect(route('areas.show', $this->area));
    }

    public function test_form_lists_questions_and_options_without_exposing_the_correct_answer(): void
    {
        $this->quiz();

        $respuesta = $this->actingAs($this->trabajador)
            ->get(route('quiz.show', $this->modulo))
            ->assertOk()
            ->assertSeeInOrder(['Pregunta 1', 'Pregunta 2', 'Pregunta 3'])
            ->assertSee('Correcta 1')
            ->assertSee('Incorrecta A1')
            ->assertSee('type="radio"', false)
            ->assertDontSee('es_correcta');

        $opcion = $respuesta->viewData('quiz')->preguntas->first()->opciones->first();
        $this->assertSame(['id', 'pregunta_id', 'texto'], array_keys($opcion->getAttributes()));
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: int|null, 3: int, 4: bool}>
     */
    public static function calificaciones(): array
    {
        return [
            'todo correcto' => [3, 3, null, 100, true],
            'dos de tres con nota global' => [3, 2, null, 66, false],
            'dos de tres con nota propia de 60' => [3, 2, 60, 66, true],
            'nada correcto' => [3, 0, null, 0, false],
            'justo en el limite' => [10, 7, null, 70, true],
            'justo debajo del limite' => [10, 6, null, 60, false],
            'nota propia exigente' => [4, 3, 80, 75, false],
        ];
    }

    #[DataProvider('calificaciones')]
    public function test_grading_is_done_on_the_server(int $preguntas, int $aciertos, ?int $notaMinima, int $puntaje, bool $aprobado): void
    {
        $quiz = $this->quiz(preguntas: $preguntas, notaMinima: $notaMinima);

        $this->actingAs($this->trabajador)
            ->post(route('quiz.enviar', $this->modulo), ['respuestas' => $this->respuestas($quiz, $aciertos)])
            ->assertRedirect();

        $this->assertDatabaseHas('intentos_quiz', [
            'user_id' => $this->trabajador->id,
            'quiz_id' => $quiz->id,
            'puntaje' => $puntaje,
            'aprobado' => $aprobado,
        ]);
    }

    public function test_every_attempt_is_kept_and_the_redirect_points_to_the_new_one(): void
    {
        $quiz = $this->quiz();
        $this->actingAs($this->trabajador);

        foreach ([0, 3, 2] as $aciertos) {
            $respuesta = $this->post(route('quiz.enviar', $this->modulo), ['respuestas' => $this->respuestas($quiz, $aciertos)]);
        }

        $this->assertSame([0, 100, 66], IntentoQuiz::where('user_id', $this->trabajador->id)->orderBy('id')->pluck('puntaje')->all());
        $respuesta->assertRedirect(route('quiz.resultado', [$this->modulo, IntentoQuiz::latest('id')->first()]));
    }

    public function test_all_questions_must_be_answered(): void
    {
        $quiz = $this->quiz();
        $respuestas = $this->respuestas($quiz, 3);
        $sinResponder = array_key_last($respuestas);
        unset($respuestas[$sinResponder]);

        $this->actingAs($this->trabajador)
            ->from(route('quiz.show', $this->modulo))
            ->post(route('quiz.enviar', $this->modulo), ['respuestas' => $respuestas])
            ->assertRedirect(route('quiz.show', $this->modulo))
            ->assertSessionHasErrors("respuestas.{$sinResponder}");

        $this->assertDatabaseCount('intentos_quiz', 0);

        $this->followingRedirects()
            ->get(route('quiz.show', $this->modulo))
            ->assertOk();
    }

    public function test_options_from_other_questions_or_nonexistent_ones_count_as_wrong(): void
    {
        $quiz = $this->quiz();
        $preguntas = $quiz->preguntas()->with('opciones')->get();
        $correctaDeLaPrimera = $preguntas[0]->opciones->firstWhere('es_correcta', true)->id;

        $this->actingAs($this->trabajador)->post(route('quiz.enviar', $this->modulo), ['respuestas' => [
            $preguntas[0]->id => $correctaDeLaPrimera,
            $preguntas[1]->id => $correctaDeLaPrimera,
            $preguntas[2]->id => 999999,
        ]])->assertRedirect();

        $intento = IntentoQuiz::first();
        $this->assertSame(33, $intento->puntaje);
        $this->assertNull($intento->respuestas[$preguntas[2]->id]);
    }

    public function test_result_shows_score_failed_questions_and_reveals_the_correct_answer(): void
    {
        $quiz = $this->quiz();
        $this->actingAs($this->trabajador)->post(route('quiz.enviar', $this->modulo), ['respuestas' => $this->respuestas($quiz, 2)]);
        $intento = IntentoQuiz::first();

        $this->get(route('quiz.resultado', [$this->modulo, $intento]))
            ->assertOk()
            ->assertSee('66%')
            ->assertSee('No aprobado')
            ->assertSee('2 de 3 respuestas correctas')
            ->assertSee('Nota mínima: 70%')
            ->assertSee('Pregunta 3')
            ->assertSee('Tu respuesta:')
            ->assertSee('Incorrecta')
            ->assertSee('Respuesta correcta:')
            ->assertSee('Correcta 3')
            ->assertDontSee('Pregunta 1')
            ->assertSee(route('quiz.show', $this->modulo), false)
            ->assertSee(route('modulos.show', $this->modulo), false);
    }

    public function test_result_of_a_perfect_attempt_celebrates_and_lists_no_failures(): void
    {
        $quiz = $this->quiz();
        $this->actingAs($this->trabajador)->post(route('quiz.enviar', $this->modulo), ['respuestas' => $this->respuestas($quiz, 3)]);

        $this->get(route('quiz.resultado', [$this->modulo, IntentoQuiz::first()]))
            ->assertOk()
            ->assertSee('100%')
            ->assertSee('Aprobado')
            ->assertSee('¡Respondiste todo correctamente!')
            ->assertDontSee('Respuesta correcta:');
    }

    public function test_result_is_only_visible_to_the_owner_of_the_attempt_and_for_its_own_module(): void
    {
        $quiz = $this->quiz();
        $otro = User::factory()->create(['rol' => 'trabajador']);
        $otro->areas()->attach($this->area);
        $ajeno = IntentoQuiz::factory()->create(['user_id' => $otro->id, 'quiz_id' => $quiz->id]);

        $otroModulo = $this->moduloConLeccion($this->area, completada: true);
        $otroQuiz = $this->quiz($otroModulo);
        $propio = IntentoQuiz::factory()->create(['user_id' => $this->trabajador->id, 'quiz_id' => $otroQuiz->id]);

        $this->actingAs($this->trabajador);

        $this->get(route('quiz.resultado', [$this->modulo, $ajeno]))->assertNotFound();
        $this->get(route('quiz.resultado', [$this->modulo, $propio]))->assertNotFound();
        $this->get(route('quiz.resultado', [$otroModulo, $propio]))->assertOk();
    }

    public function test_admin_can_take_the_quiz_without_completing_the_lessons(): void
    {
        $pendiente = $this->moduloConLeccion($this->area, completada: false);
        $this->quiz($pendiente);
        $admin = User::factory()->create(['rol' => 'admin']);

        $this->actingAs($admin)->get(route('quiz.show', $pendiente))->assertOk();
    }

    public function test_module_page_offers_the_quiz_only_when_lessons_are_completed(): void
    {
        $this->quiz();

        $this->actingAs($this->trabajador)
            ->get(route('modulos.show', $this->modulo))
            ->assertOk()
            ->assertSee('Rendir quiz del módulo')
            ->assertSee(route('quiz.show', $this->modulo), false);

        $pendiente = $this->moduloConLeccion($this->area, completada: false);
        $this->quiz($pendiente);
        $this->modulo->update(['orden' => 0]);

        $this->get(route('modulos.show', $pendiente))
            ->assertOk()
            ->assertSee('Completa todas las lecciones para desbloquear el quiz.')
            ->assertDontSee(route('quiz.show', $pendiente), false);
    }
}
