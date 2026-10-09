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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EncuestaAreaTest extends TestCase
{
    use RefreshDatabase;

    private const PREGUNTA = '¿Qué tan claro fue el contenido?';

    private User $trabajador;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trabajador = User::factory()->create(['rol' => 'trabajador']);
        $this->area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);
        $this->trabajador->areas()->attach($this->area);
    }

    private function completar(Area $area, bool $vistas = true, string $titulo = 'Introduccion'): Modulo
    {
        $modulo = Modulo::create(['area_id' => $area->id, 'titulo' => $titulo]);
        $leccion = Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'Leccion', 'activa' => true]);

        if ($vistas) {
            $this->trabajador->lecciones()->attach($leccion);
        }

        return $modulo;
    }

    private function respuesta(array $cambios = []): array
    {
        return $cambios + ['claridad' => 5, 'utilidad' => 4, 'ritmo' => 3, 'comentario' => 'Muy buen contenido'];
    }

    // ------------------------------------------------- tarjeta en la lista

    public function test_survey_is_the_last_step_of_the_list_once_the_area_is_completed(): void
    {
        $this->completar($this->area, titulo: 'Modulo unico');

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', $this->area))
            ->assertOk()
            ->assertSeeInOrder(['Modulo unico', 'Encuesta de satisfacción', 'Pendiente'])
            ->assertSee('href="'.route('areas.encuesta', $this->area).'"', false);
    }

    public function test_questions_are_not_shown_on_the_area_page(): void
    {
        $this->completar($this->area);

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', $this->area))
            ->assertOk()
            ->assertDontSee(self::PREGUNTA)
            ->assertDontSee('Enviar mi opinión')
            ->assertDontSee('name="claridad"', false);
    }

    public function test_survey_step_is_hidden_while_the_area_is_incomplete(): void
    {
        $this->completar($this->area, vistas: false);

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', $this->area))
            ->assertOk()
            ->assertDontSee('Encuesta de satisfacción')
            ->assertDontSee(route('areas.encuesta', $this->area), false);
    }

    public function test_survey_step_is_hidden_while_any_module_is_still_pending(): void
    {
        $this->completar($this->area);
        $this->completar($this->area, vistas: false, titulo: 'Modulo pendiente');

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', $this->area))
            ->assertOk()
            ->assertSee('Modulo pendiente')
            ->assertDontSee('Encuesta de satisfacción');
    }

    public function test_survey_step_is_hidden_while_a_quiz_is_not_approved(): void
    {
        $modulo = $this->completar($this->area);
        $quiz = Quiz::create(['modulo_id' => $modulo->id, 'titulo' => 'Quiz']);
        $pregunta = Pregunta::create(['quiz_id' => $quiz->id, 'enunciado' => 'Pregunta', 'orden' => 1]);
        Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'Correcta', 'es_correcta' => true]);

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', $this->area))
            ->assertOk()
            ->assertDontSee('Encuesta de satisfacción');

        IntentoQuiz::factory()->create(['user_id' => $this->trabajador->id, 'quiz_id' => $quiz->id, 'puntaje' => 100, 'aprobado' => true]);

        $this->get(route('areas.show', $this->area))->assertSee('Encuesta de satisfacción');
    }

    public function test_survey_step_goes_after_the_modules_and_before_the_download(): void
    {
        Storage::fake(config('filament.default_filesystem_disk'));
        Storage::disk(config('filament.default_filesystem_disk'))->put('resumenes/area.pdf', '%PDF');
        $this->area->update(['resumen_pdf' => 'resumenes/area.pdf', 'descripcion' => 'Descripcion del area']);
        $this->completar($this->area, titulo: 'Modulo unico');

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', $this->area))
            ->assertOk()
            ->assertSeeInOrder([
                'Volver al inicio',
                'Descripcion del area',
                'Modulo unico',
                'Encuesta de satisfacción',
                'Descargar resumen del área',
            ]);
    }

    public function test_download_and_survey_step_are_both_hidden_until_everything_is_finished(): void
    {
        Storage::fake(config('filament.default_filesystem_disk'));
        $this->area->update(['resumen_pdf' => 'resumenes/area.pdf']);
        $this->completar($this->area);
        $this->completar($this->area, vistas: false);

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', $this->area))
            ->assertOk()
            ->assertDontSee('Descargar resumen del área')
            ->assertDontSee('Encuesta de satisfacción');
    }

    public function test_answered_survey_shows_as_done_without_a_link(): void
    {
        $this->completar($this->area);
        Feedback::factory()->create(['user_id' => $this->trabajador->id, 'area_id' => $this->area->id]);

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', $this->area))
            ->assertOk()
            ->assertSee('Encuesta de satisfacción')
            ->assertSee('Respondida')
            ->assertDontSee('Pendiente')
            ->assertDontSee(route('areas.encuesta', $this->area), false);
    }

    public function test_survey_step_is_not_offered_to_admins(): void
    {
        $this->completar($this->area);
        $admin = User::factory()->create(['rol' => 'admin']);

        $this->actingAs($admin)
            ->get(route('areas.show', $this->area))
            ->assertOk()
            ->assertDontSee('Encuesta de satisfacción');
    }

    // ------------------------------------------------ pantalla de la encuesta

    public function test_survey_screen_shows_the_questions(): void
    {
        $this->completar($this->area);

        $this->actingAs($this->trabajador)
            ->get(route('areas.encuesta', $this->area))
            ->assertOk()
            ->assertSee(self::PREGUNTA)
            ->assertSee('¿Qué tan útil te resultó para tu trabajo?')
            ->assertSee('¿Qué tan adecuado fue el ritmo?')
            ->assertSee('Comentario')
            ->assertSee('action="'.route('areas.feedback', $this->area).'"', false)
            ->assertSee('href="'.route('areas.show', $this->area).'"', false)
            ->assertSee('Enviar mi opinión');
    }

    public function test_survey_screen_redirects_to_the_area_until_it_is_completed(): void
    {
        $this->completar($this->area, vistas: false);

        $this->actingAs($this->trabajador)
            ->get(route('areas.encuesta', $this->area))
            ->assertRedirect(route('areas.show', $this->area))
            ->assertSessionHas('aviso');
    }

    public function test_survey_screen_redirects_to_the_area_once_answered(): void
    {
        $this->completar($this->area);
        Feedback::factory()->create(['user_id' => $this->trabajador->id, 'area_id' => $this->area->id]);

        $this->actingAs($this->trabajador)
            ->get(route('areas.encuesta', $this->area))
            ->assertRedirect(route('areas.show', $this->area))
            ->assertSessionHas('aviso');
    }

    public function test_survey_screen_rejects_guests_unassigned_workers_and_inactive_areas(): void
    {
        $ajena = Area::create(['nombre' => 'Ajena', 'slug' => 'ajena']);
        $inactiva = Area::create(['nombre' => 'Inactiva', 'slug' => 'inactiva', 'activa' => false]);
        $this->trabajador->areas()->attach($inactiva);

        $this->get(route('areas.encuesta', $this->area))->assertRedirect('/login');

        $this->actingAs($this->trabajador);
        $this->get(route('areas.encuesta', $ajena))->assertForbidden();
        $this->get(route('areas.encuesta', $inactiva))->assertNotFound();
    }

    // ------------------------------------------------------------- guardado

    public function test_answers_are_saved_and_the_worker_returns_to_the_area_thanked(): void
    {
        $this->completar($this->area);

        $this->actingAs($this->trabajador)
            ->post(route('areas.feedback', $this->area), $this->respuesta(['comentario' => '  Muy buen contenido  ']))
            ->assertRedirect(route('areas.show', $this->area))
            ->assertSessionHas('estado');

        $this->assertDatabaseHas('feedback', [
            'user_id' => $this->trabajador->id,
            'area_id' => $this->area->id,
            'claridad' => 5,
            'utilidad' => 4,
            'ritmo' => 3,
            'comentario' => 'Muy buen contenido',
        ]);

        $this->followingRedirects()
            ->get(route('areas.show', $this->area))
            ->assertSee('¡Gracias por tu opinión!')
            ->assertSee('Respondida')
            ->assertDontSee('Pendiente');
    }

    public function test_comment_is_optional(): void
    {
        $this->completar($this->area);
        $this->actingAs($this->trabajador);

        $this->post(route('areas.feedback', $this->area), $this->respuesta(['comentario' => '   ']))->assertSessionHasNoErrors();

        $this->assertNull(Feedback::first()->comentario);
    }

    public function test_a_second_answer_never_duplicates_or_overwrites_the_first(): void
    {
        $this->completar($this->area);
        $this->actingAs($this->trabajador);

        $this->post(route('areas.feedback', $this->area), $this->respuesta());
        $this->post(route('areas.feedback', $this->area), $this->respuesta(['claridad' => 1, 'comentario' => 'Otro']))
            ->assertRedirect(route('areas.show', $this->area))
            ->assertSessionHas('aviso');

        $this->assertDatabaseCount('feedback', 1);
        $this->assertSame(5, Feedback::first()->claridad);
        $this->assertSame('Muy buen contenido', Feedback::first()->comentario);
    }

    public function test_the_same_worker_can_answer_different_areas(): void
    {
        $otraArea = Area::create(['nombre' => 'Seguridad', 'slug' => 'seguridad']);
        $this->trabajador->areas()->attach($otraArea);
        $this->completar($this->area);
        $this->completar($otraArea);
        $this->actingAs($this->trabajador);

        $this->post(route('areas.feedback', $this->area), $this->respuesta());
        $this->post(route('areas.feedback', $otraArea), $this->respuesta());

        $this->assertDatabaseCount('feedback', 2);
    }

    public function test_ratings_must_be_whole_numbers_between_one_and_five(): void
    {
        $this->completar($this->area);
        $this->actingAs($this->trabajador);

        foreach ([0, 6, -1, 'abc', 2.5, ''] as $invalida) {
            $this->post(route('areas.feedback', $this->area), $this->respuesta(['claridad' => $invalida]))
                ->assertSessionHasErrors('claridad');
        }

        $this->post(route('areas.feedback', $this->area), ['comentario' => 'Solo comentario'])
            ->assertSessionHasErrors(['claridad', 'utilidad', 'ritmo']);

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_comment_cannot_exceed_the_column_limit(): void
    {
        $this->completar($this->area);

        $this->actingAs($this->trabajador)
            ->post(route('areas.feedback', $this->area), $this->respuesta(['comentario' => str_repeat('a', 601)]))
            ->assertSessionHasErrors('comentario');

        $this->actingAs($this->trabajador)
            ->post(route('areas.feedback', $this->area), $this->respuesta(['comentario' => str_repeat('a', 600)]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('feedback', 1);
    }

    public function test_errors_are_shown_on_the_survey_screen_after_a_failed_submit(): void
    {
        $this->completar($this->area);

        $this->actingAs($this->trabajador)
            ->from(route('areas.encuesta', $this->area))
            ->followingRedirects()
            ->post(route('areas.feedback', $this->area), ['comentario' => 'Sin notas'])
            ->assertSee(self::PREGUNTA)
            ->assertSee('Califica la claridad del contenido.')
            ->assertSee('Sin notas');
    }

    public function test_answers_are_rejected_until_the_area_is_completed(): void
    {
        $this->completar($this->area, vistas: false);

        $this->actingAs($this->trabajador)
            ->post(route('areas.feedback', $this->area), $this->respuesta())
            ->assertRedirect(route('areas.show', $this->area))
            ->assertSessionHas('aviso');

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_saving_rejects_guests_unassigned_workers_and_inactive_areas(): void
    {
        $ajena = Area::create(['nombre' => 'Ajena', 'slug' => 'ajena']);
        $inactiva = Area::create(['nombre' => 'Inactiva', 'slug' => 'inactiva', 'activa' => false]);
        $this->trabajador->areas()->attach($inactiva);

        $this->post(route('areas.feedback', $this->area), $this->respuesta())->assertRedirect('/login');

        $this->actingAs($this->trabajador);
        $this->post(route('areas.feedback', $ajena), $this->respuesta())->assertForbidden();
        $this->post(route('areas.feedback', $inactiva), $this->respuesta())->assertNotFound();

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_feedback_model_uses_the_existing_table_and_its_relations(): void
    {
        $feedback = Feedback::factory()->create(['user_id' => $this->trabajador->id, 'area_id' => $this->area->id]);

        $this->assertSame('feedback', $feedback->getTable());
        $this->assertTrue($feedback->user->is($this->trabajador));
        $this->assertTrue($feedback->area->is($this->area));
    }
}
