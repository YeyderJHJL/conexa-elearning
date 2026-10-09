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
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DescargaResumenTest extends TestCase
{
    use RefreshDatabase;

    private const CONTENIDO_PDF = '%PDF-1.4 resumen de prueba';

    private User $trabajador;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $disco = $this->disco();
        $disco->put('resumenes/modulo.pdf', self::CONTENIDO_PDF);
        $disco->put('resumenes/area.pdf', self::CONTENIDO_PDF);

        $this->trabajador = User::factory()->create(['rol' => 'trabajador']);
        $this->area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas', 'resumen_pdf' => 'resumenes/area.pdf']);
        $this->trabajador->areas()->attach($this->area);
    }

    private function disco(): Filesystem
    {
        $nombre = config('filament.default_filesystem_disk');
        Storage::fake($nombre);

        return Storage::disk($nombre);
    }

    private function modulo(string $titulo = 'Introduccion', array $atributos = [], ?Area $area = null): Modulo
    {
        return Modulo::create($atributos + ['area_id' => ($area ?? $this->area)->id, 'titulo' => $titulo]);
    }

    private function leccion(Modulo $modulo, bool $vista = true, int $orden = 0): Leccion
    {
        $leccion = Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'Leccion '.$orden, 'orden' => $orden, 'activa' => true]);

        if ($vista) {
            $this->trabajador->lecciones()->attach($leccion);
        }

        return $leccion;
    }

    private function quiz(Modulo $modulo): Quiz
    {
        $quiz = Quiz::create(['modulo_id' => $modulo->id, 'titulo' => 'Quiz']);
        $pregunta = Pregunta::create(['quiz_id' => $quiz->id, 'enunciado' => 'Pregunta', 'orden' => 1]);
        Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'Correcta', 'es_correcta' => true]);
        Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'Incorrecta', 'es_correcta' => false]);

        return $quiz;
    }

    private function intento(Quiz $quiz, int $puntaje): IntentoQuiz
    {
        return IntentoQuiz::factory()->create([
            'user_id' => $this->trabajador->id,
            'quiz_id' => $quiz->id,
            'puntaje' => $puntaje,
            'aprobado' => $puntaje >= 70,
        ]);
    }

    /**
     * Módulo con una lección vista y un quiz aprobado.
     */
    private function moduloCompleto(string $titulo = 'Introduccion', array $atributos = [], ?Area $area = null): Modulo
    {
        $modulo = $this->modulo($titulo, $atributos, $area);
        $this->leccion($modulo);
        $this->intento($this->quiz($modulo), 90);

        return $modulo;
    }

    private function moduloConResumen(array $atributos = []): Modulo
    {
        return $this->moduloCompleto(atributos: ['resumen_pdf' => 'resumenes/modulo.pdf'] + $atributos);
    }

    private function moduloPendiente(array $atributos = []): Modulo
    {
        $modulo = $this->modulo('Pendiente', ['resumen_pdf' => 'resumenes/modulo.pdf'] + $atributos);
        $this->leccion($modulo, vista: false);

        return $modulo;
    }

    // ---------------------------------------------------------------- módulo

    public function test_guests_are_redirected_to_login(): void
    {
        $modulo = $this->moduloConResumen();

        $this->get(route('modulos.resumen', $modulo))->assertRedirect('/login');
        $this->get(route('areas.resumen', $this->area))->assertRedirect('/login');
    }

    public function test_worker_cannot_download_the_summary_of_an_unassigned_area(): void
    {
        $ajena = Area::create(['nombre' => 'Seguridad', 'slug' => 'seguridad', 'resumen_pdf' => 'resumenes/area.pdf']);
        $modulo = $this->moduloCompleto('Ajeno', ['resumen_pdf' => 'resumenes/modulo.pdf'], $ajena);

        $this->actingAs($this->trabajador);

        $this->get(route('modulos.resumen', $modulo))->assertForbidden();
        $this->get(route('areas.resumen', $ajena))->assertForbidden();
    }

    public function test_module_summary_is_downloaded_once_the_module_is_completed(): void
    {
        $modulo = $this->moduloConResumen(['titulo' => 'Introduccion a ventas']);

        $respuesta = $this->actingAs($this->trabajador)
            ->get(route('modulos.resumen', $modulo))
            ->assertOk()
            ->assertDownload('resumen-modulo-introduccion-a-ventas.pdf');

        $this->assertSame(self::CONTENIDO_PDF, $respuesta->streamedContent());
    }

    public function test_module_summary_is_not_available_until_the_module_is_completed(): void
    {
        $pendiente = $this->moduloPendiente();

        $this->actingAs($this->trabajador)
            ->get(route('modulos.resumen', $pendiente))
            ->assertRedirect(route('modulos.show', $pendiente))
            ->assertSessionHas('aviso');
    }

    public function test_module_summary_requires_the_quiz_to_be_approved(): void
    {
        $modulo = $this->modulo('Con quiz', ['resumen_pdf' => 'resumenes/modulo.pdf']);
        $this->leccion($modulo);
        $this->intento($this->quiz($modulo), 40);

        $this->actingAs($this->trabajador)
            ->get(route('modulos.resumen', $modulo))
            ->assertRedirect(route('modulos.show', $modulo))
            ->assertSessionHas('aviso');
    }

    public function test_module_locked_by_the_sequence_redirects_to_the_area(): void
    {
        $primero = $this->modulo('Primero', ['orden' => 1]);
        $this->leccion($primero, vista: false);
        $segundo = $this->moduloCompleto('Segundo', ['orden' => 2, 'resumen_pdf' => 'resumenes/modulo.pdf']);

        $this->actingAs($this->trabajador)
            ->get(route('modulos.resumen', $segundo))
            ->assertRedirect(route('areas.show', $this->area));
    }

    public function test_module_without_a_summary_or_with_a_missing_file_is_not_found(): void
    {
        $sinResumen = $this->moduloCompleto('Sin resumen');
        $archivoPerdido = $this->moduloCompleto('Archivo perdido', ['resumen_pdf' => 'resumenes/no-existe.pdf']);

        $this->actingAs($this->trabajador);

        $this->get(route('modulos.resumen', $sinResumen))->assertNotFound();
        $this->get(route('modulos.resumen', $archivoPerdido))->assertNotFound();
    }

    public function test_inactive_module_is_not_found_for_a_worker(): void
    {
        $modulo = $this->moduloConResumen(['activo' => false]);

        $this->actingAs($this->trabajador)->get(route('modulos.resumen', $modulo))->assertNotFound();
    }

    public function test_admin_can_download_the_module_summary_without_completing_it(): void
    {
        $pendiente = $this->moduloPendiente();
        $admin = User::factory()->create(['rol' => 'admin']);

        $this->actingAs($admin)->get(route('modulos.resumen', $pendiente))->assertOk()->assertDownload();
    }

    // ------------------------------------------------------------------ área

    public function test_area_summary_is_downloaded_once_the_area_is_completed(): void
    {
        $this->moduloCompleto('Uno');
        $this->moduloCompleto('Dos');

        $respuesta = $this->actingAs($this->trabajador)
            ->get(route('areas.resumen', $this->area))
            ->assertOk()
            ->assertDownload('resumen-area-ventas.pdf');

        $this->assertSame(self::CONTENIDO_PDF, $respuesta->streamedContent());
    }

    public function test_area_summary_is_not_available_while_any_module_is_pending(): void
    {
        $this->moduloCompleto('Hecho');
        $this->moduloPendiente();

        $this->actingAs($this->trabajador)
            ->get(route('areas.resumen', $this->area))
            ->assertRedirect(route('areas.show', $this->area))
            ->assertSessionHas('aviso');
    }

    public function test_area_summary_is_not_available_for_an_area_without_content(): void
    {
        $this->modulo('Vacio');

        $this->actingAs($this->trabajador)
            ->get(route('areas.resumen', $this->area))
            ->assertRedirect(route('areas.show', $this->area));
    }

    public function test_area_without_a_summary_or_inactive_is_not_found(): void
    {
        $this->moduloCompleto();
        $sinResumen = Area::create(['nombre' => 'Sin resumen', 'slug' => 'sin-resumen']);
        $inactiva = Area::create(['nombre' => 'Inactiva', 'slug' => 'inactiva', 'activa' => false, 'resumen_pdf' => 'resumenes/area.pdf']);
        $this->trabajador->areas()->attach([$sinResumen->id, $inactiva->id]);

        $this->actingAs($this->trabajador);

        $this->get(route('areas.resumen', $sinResumen))->assertNotFound();
        $this->get(route('areas.resumen', $inactiva))->assertNotFound();
    }

    public function test_admin_can_download_the_area_summary_without_completing_it(): void
    {
        $this->moduloPendiente();
        $admin = User::factory()->create(['rol' => 'admin']);

        $this->actingAs($admin)->get(route('areas.resumen', $this->area))->assertOk()->assertDownload();
    }

    public function test_area_completion_rule(): void
    {
        $servicio = app(ProgresoService::class);

        $this->assertFalse($servicio->areaCompletada($this->trabajador, $this->area));

        $this->modulo('Vacio');
        $this->assertFalse($servicio->areaCompletada($this->trabajador, $this->area), 'Un módulo sin contenido no completa el área.');

        $conQuiz = $this->modulo('Con quiz');
        $this->leccion($conQuiz);
        $quiz = $this->quiz($conQuiz);
        $this->assertFalse($servicio->areaCompletada($this->trabajador, $this->area), 'Falta aprobar el quiz.');

        $this->intento($quiz, 100);
        $this->assertTrue($servicio->areaCompletada($this->trabajador, $this->area));
    }

    // --------------------------------------------------------------- botones

    public function test_module_page_shows_the_button_only_with_a_summary_and_a_completed_module(): void
    {
        $completo = $this->moduloConResumen();

        $this->actingAs($this->trabajador)
            ->get(route('modulos.show', $completo))
            ->assertOk()
            ->assertSee('Descargar resumen del módulo')
            ->assertSee('href="'.route('modulos.resumen', $completo).'"', false);
    }

    public function test_module_page_hides_the_button_when_incomplete_or_without_a_summary(): void
    {
        $sinResumen = $this->moduloCompleto('Sin resumen', ['orden' => 1]);
        $pendiente = $this->moduloPendiente(['orden' => 2]);

        $this->actingAs($this->trabajador);

        $this->get(route('modulos.show', $pendiente))->assertOk()->assertDontSee('Descargar resumen del módulo');
        $this->get(route('modulos.show', $sinResumen))->assertOk()->assertDontSee('Descargar resumen del módulo');
    }

    public function test_pages_never_expose_a_public_storage_url(): void
    {
        $modulo = $this->moduloConResumen();

        $this->actingAs($this->trabajador);

        foreach ([route('modulos.show', $modulo), route('areas.show', $this->area)] as $url) {
            $this->get($url)->assertOk()->assertDontSee('resumenes/')->assertDontSee('/storage/');
        }
    }

    public function test_area_page_shows_the_button_only_with_a_summary_and_a_completed_area(): void
    {
        $this->actingAs($this->trabajador);
        $this->moduloPendiente();

        $this->get(route('areas.show', $this->area))->assertOk()->assertDontSee('Descargar resumen del área');

        $this->leccion(Modulo::first(), vista: false, orden: 1);
        Modulo::first()->lecciones()->each(fn (Leccion $leccion) => $this->trabajador->lecciones()->syncWithoutDetaching([$leccion->id]));

        $this->get(route('areas.show', $this->area))
            ->assertOk()
            ->assertSee('Descargar resumen del área')
            ->assertSee('href="'.route('areas.resumen', $this->area).'"', false);
    }

    public function test_area_page_hides_the_button_when_the_area_has_no_summary(): void
    {
        $sinResumen = Area::create(['nombre' => 'Sin resumen', 'slug' => 'sin-resumen']);
        $this->trabajador->areas()->attach($sinResumen);
        $this->moduloCompleto('Hecho', area: $sinResumen);

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', $sinResumen))
            ->assertOk()
            ->assertDontSee('Descargar resumen del área');
    }

    public function test_lessons_have_no_summary_download_and_the_generated_summary_is_gone(): void
    {
        $modulo = $this->moduloConResumen();
        $leccion = $modulo->lecciones->first();

        $this->actingAs($this->trabajador)
            ->get(route('lecciones.show', $leccion))
            ->assertOk()
            ->assertDontSee('Descargar resumen');

        $this->assertFalse(view()->exists('pdf.resumen-modulo'));
        $this->assertTrue(view()->exists('pdf.reporte'), 'El reporte de avance del admin se conserva.');
    }
}
