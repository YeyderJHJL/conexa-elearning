<?php

namespace Tests\Feature;

use App\Filament\Pages\ReporteAvance;
use App\Filament\Resources\Feedbacks\FeedbackResource;
use App\Filament\Resources\Feedbacks\Pages\ListFeedbacks;
use App\Models\Area;
use App\Models\Feedback;
use App\Models\IntentoQuiz;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Quiz;
use App\Models\User;
use App\Services\ReporteAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReporteAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['name' => 'Admin', 'rol' => 'admin']);
        $this->area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);
    }

    private function trabajador(string $nombre, ?Area $area = null): User
    {
        $trabajador = User::factory()->create(['name' => $nombre, 'rol' => 'trabajador', 'cargo' => 'Asesor']);
        $trabajador->areas()->attach($area ?? $this->area);

        return $trabajador;
    }

    /**
     * Módulo con una lección vista por el usuario y un quiz.
     */
    private function moduloConQuiz(User $usuario, Area $area, string $titulo = 'Introduccion'): Quiz
    {
        $modulo = Modulo::create(['area_id' => $area->id, 'titulo' => $titulo]);
        $leccion = Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'Leccion', 'activa' => true]);
        $usuario->lecciones()->attach($leccion);

        $quiz = Quiz::create(['modulo_id' => $modulo->id, 'titulo' => "Quiz {$titulo}"]);
        $pregunta = Pregunta::create(['quiz_id' => $quiz->id, 'enunciado' => 'Pregunta', 'orden' => 1]);
        Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'Correcta', 'es_correcta' => true]);
        Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'Incorrecta', 'es_correcta' => false]);

        return $quiz;
    }

    private function intento(User $usuario, Quiz $quiz, int $puntaje): void
    {
        IntentoQuiz::factory()->create([
            'user_id' => $usuario->id,
            'quiz_id' => $quiz->id,
            'puntaje' => $puntaje,
            'aprobado' => $puntaje >= 70,
        ]);
    }

    // ------------------------------------------------------------ acceso

    public function test_admin_can_open_the_report_and_feedback_pages(): void
    {
        $this->actingAs($this->admin);

        $this->get('/admin/reporte-avance')->assertOk()->assertSee('Reporte de avance');
        $this->get(FeedbackResource::getUrl('index'))->assertOk()->assertSee('Encuestas de feedback');
        $this->assertSame(url('/admin/feedback'), FeedbackResource::getUrl('index'));
    }

    public function test_workers_and_guests_cannot_open_the_admin_pages(): void
    {
        $trabajador = $this->trabajador('Ana');

        $this->get('/admin/reporte-avance')->assertRedirect();
        $this->get(FeedbackResource::getUrl('index'))->assertRedirect();

        $this->actingAs($trabajador);
        $this->get('/admin/reporte-avance')->assertForbidden();
        $this->get(FeedbackResource::getUrl('index'))->assertForbidden();
    }

    // ----------------------------------------------------------- reporte

    public function test_report_table_lists_only_workers_with_their_progress_and_quiz_notes(): void
    {
        $ana = $this->trabajador('Ana Torres');
        $luis = $this->trabajador('Luis Perez');
        $quiz = $this->moduloConQuiz($ana, $this->area);
        $this->intento($ana, $quiz, 40);
        $this->intento($ana, $quiz, 90);

        Livewire::actingAs($this->admin)
            ->test(ReporteAvance::class)
            ->assertCanSeeTableRecords([$ana, $luis])
            ->assertCanNotSeeTableRecords([$this->admin])
            ->assertTableColumnStateSet('avance', 100, $ana)
            ->assertTableColumnStateSet('quizzes', '1 de 1', $ana)
            ->assertTableColumnStateSet('promedio', 90, $ana)
            ->assertTableColumnStateSet('avance', 0, $luis)
            ->assertTableColumnStateSet('quizzes', 'Sin rendir', $luis)
            ->assertTableColumnStateSet('promedio', null, $luis);
    }

    public function test_report_table_can_be_searched_and_filtered_by_area(): void
    {
        $otraArea = Area::create(['nombre' => 'Seguridad', 'slug' => 'seguridad']);
        $ana = $this->trabajador('Ana Torres');
        $luis = $this->trabajador('Luis Perez', $otraArea);

        Livewire::actingAs($this->admin)
            ->test(ReporteAvance::class)
            ->searchTable('Luis')
            ->assertCanSeeTableRecords([$luis])
            ->assertCanNotSeeTableRecords([$ana])
            ->searchTable('')
            ->filterTable('area', $this->area->id)
            ->assertCanSeeTableRecords([$ana])
            ->assertCanNotSeeTableRecords([$luis]);
    }

    public function test_report_table_offers_the_pdf_download_and_the_detail_of_each_worker(): void
    {
        $ana = $this->trabajador('Ana Torres');
        $quiz = $this->moduloConQuiz($ana, $this->area, 'Introduccion a ventas');
        $this->intento($ana, $quiz, 55);

        Livewire::actingAs($this->admin)
            ->test(ReporteAvance::class)
            ->assertTableActionHasUrl('descargar', route('reportes.trabajador', $ana), $ana)
            ->mountTableAction('detalle', $ana)
            ->assertMountedActionModalSee(['Introduccion a ventas', 'No aprobado', '55%', 'Puntos a reforzar']);
    }

    public function test_summary_counts_each_quiz_once_with_its_best_attempt_and_ignores_other_workers(): void
    {
        $ana = $this->trabajador('Ana Torres');
        $luis = $this->trabajador('Luis Perez');
        $primero = $this->moduloConQuiz($ana, $this->area, 'Uno');
        $segundo = $this->moduloConQuiz($ana, $this->area, 'Dos');
        $this->intento($ana, $primero, 20);
        $this->intento($ana, $primero, 85);
        $this->intento($ana, $segundo, 41);
        $this->intento($luis, $primero, 100);

        $resumen = app(ReporteAdminService::class)->resumenTrabajadores(collect([$ana, $luis]));

        $this->assertSame(['rendidos' => 2, 'aprobados' => 1, 'promedio' => 63], collect($resumen[$ana->id])->only(['rendidos', 'aprobados', 'promedio'])->all());
        $this->assertSame(['rendidos' => 1, 'aprobados' => 1, 'promedio' => 100], collect($resumen[$luis->id])->only(['rendidos', 'aprobados', 'promedio'])->all());
    }

    public function test_summary_of_a_worker_without_attempts_has_no_average(): void
    {
        $ana = $this->trabajador('Ana Torres');

        $resumen = app(ReporteAdminService::class)->resumenTrabajadores(collect([$ana]));

        $this->assertSame(['global' => 0, 'rendidos' => 0, 'aprobados' => 0, 'promedio' => null], $resumen[$ana->id]);
    }

    // ---------------------------------------------------------- feedback

    public function test_feedback_answers_are_listed_and_can_be_filtered_by_area(): void
    {
        $otraArea = Area::create(['nombre' => 'Seguridad', 'slug' => 'seguridad']);
        $ana = $this->trabajador('Ana Torres');
        $ventas = Feedback::factory()->create(['user_id' => $ana->id, 'area_id' => $this->area->id, 'comentario' => 'Excelente curso']);
        $seguridad = Feedback::factory()->create(['user_id' => $ana->id, 'area_id' => $otraArea->id, 'comentario' => null]);

        Livewire::actingAs($this->admin)
            ->test(ListFeedbacks::class)
            ->assertCanSeeTableRecords([$ventas, $seguridad])
            ->assertTableColumnStateSet('user.name', 'Ana Torres', $ventas)
            ->assertTableColumnStateSet('area.nombre', 'Ventas', $ventas)
            ->assertTableColumnStateSet('comentario', 'Excelente curso', $ventas)
            ->assertSee('Promedio')
            ->filterTable('area_id', $otraArea->id)
            ->assertCanSeeTableRecords([$seguridad])
            ->assertCanNotSeeTableRecords([$ventas]);
    }

    public function test_feedback_section_is_read_only(): void
    {
        $this->actingAs($this->admin);

        $this->assertFalse(FeedbackResource::canCreate());
        $this->assertFalse(FeedbackResource::canEdit(new Feedback));
        $this->assertFalse(FeedbackResource::canDelete(new Feedback));
        $this->assertFalse(FeedbackResource::canDeleteAny());
        $this->assertSame(['index'], array_keys(FeedbackResource::getPages()));
    }
}
