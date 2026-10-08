<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ModuloLeccionTest extends TestCase
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
        $this->modulo = Modulo::create(['area_id' => $this->area->id, 'titulo' => 'Modulo uno', 'orden' => 1]);
    }

    private function leccion(array $atributos = [], ?Modulo $modulo = null): Leccion
    {
        return Leccion::create($atributos + ['modulo_id' => ($modulo ?? $this->modulo)->id, 'titulo' => 'Leccion', 'activa' => true]);
    }

    private function modeloAjeno(): Modulo
    {
        $ajena = Area::create(['nombre' => 'Seguridad', 'slug' => 'seguridad']);

        return Modulo::create(['area_id' => $ajena->id, 'titulo' => 'Modulo ajeno']);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $leccion = $this->leccion();

        $this->get(route('modulos.show', $this->modulo))->assertRedirect('/login');
        $this->get(route('lecciones.show', $leccion))->assertRedirect('/login');
    }

    public function test_worker_cannot_open_module_lesson_or_pdf_of_an_unassigned_area(): void
    {
        $ajeno = $this->modeloAjeno();
        $leccionAjena = $this->leccion(['archivo_pdf' => 'lecciones/x.pdf'], $ajeno);

        $this->actingAs($this->trabajador);

        $this->get(route('modulos.show', $ajeno))->assertForbidden();
        $this->get(route('lecciones.show', $leccionAjena))->assertForbidden();
        $this->get(route('lecciones.pdf', $leccionAjena))->assertForbidden();
        $this->post(route('lecciones.completar', $leccionAjena))->assertForbidden();
        $this->assertDatabaseCount('leccion_user', 0);
    }

    public function test_module_lists_active_lessons_in_order_and_marks_completed_ones(): void
    {
        $segunda = $this->leccion(['titulo' => 'Segunda leccion', 'orden' => 2]);
        $primera = $this->leccion(['titulo' => 'Primera leccion', 'orden' => 1]);
        $this->leccion(['titulo' => 'Leccion oculta', 'orden' => 3, 'activa' => false]);
        $this->trabajador->lecciones()->attach($primera);

        $respuesta = $this->actingAs($this->trabajador)
            ->get(route('modulos.show', $this->modulo))
            ->assertOk()
            ->assertSeeInOrder(['Primera leccion', 'Segunda leccion'])
            ->assertDontSee('Leccion oculta');

        $this->assertSame(1, substr_count($respuesta->getContent(), 'bi-check-circle-fill'));
        $this->assertSame(1, substr_count($respuesta->getContent(), 'bi-circle '));
    }

    public function test_lesson_shows_embedded_video_sanitized_content_and_pdf_block(): void
    {
        $leccion = $this->leccion([
            'url_video' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'contenido' => '<p>Texto seguro</p><script>alert("xss")</script>',
            'archivo_pdf' => 'lecciones/guia.pdf',
        ]);

        $this->actingAs($this->trabajador)
            ->get(route('lecciones.show', $leccion))
            ->assertOk()
            ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('Texto seguro')
            ->assertDontSee('<script>alert', false)
            ->assertSee(route('lecciones.pdf', $leccion), false);
    }

    /**
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function videoUrls(): array
    {
        return [
            'youtube watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10s', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'youtube corto' => ['https://youtu.be/dQw4w9WgXcQ?si=abc', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'youtube shorts' => ['https://youtube.com/shorts/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'youtube embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'drive view' => ['https://drive.google.com/file/d/1AbC_dEf-123/view?usp=sharing', 'https://drive.google.com/file/d/1AbC_dEf-123/preview'],
            'drive open' => ['https://drive.google.com/open?id=1AbC_dEf-123', 'https://drive.google.com/file/d/1AbC_dEf-123/preview'],
            'otro sitio' => ['https://example.com/video.mp4', null],
            'host engañoso' => ['https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ', null],
            'esquema peligroso' => ['javascript:alert(1)', null],
            'sin url' => ['', null],
        ];
    }

    #[DataProvider('videoUrls')]
    public function test_video_url_is_converted_to_embed_format(string $url, ?string $esperada): void
    {
        $this->assertSame($esperada, (new Leccion(['url_video' => $url]))->video_embed_url);
    }

    public function test_completing_a_lesson_saves_progress_and_goes_to_the_next_one(): void
    {
        $primera = $this->leccion(['orden' => 1]);
        $segunda = $this->leccion(['orden' => 2]);

        $this->actingAs($this->trabajador)
            ->post(route('lecciones.completar', $primera))
            ->assertRedirect(route('lecciones.show', $segunda));

        $this->assertDatabaseHas('leccion_user', ['user_id' => $this->trabajador->id, 'leccion_id' => $primera->id]);
    }

    public function test_completing_the_last_lesson_returns_to_the_module_without_overwriting_the_date(): void
    {
        $unica = $this->leccion();
        $this->trabajador->lecciones()->attach($unica, ['completada_en' => '2026-01-01 10:00:00']);

        $this->actingAs($this->trabajador)
            ->post(route('lecciones.completar', $unica))
            ->assertRedirect(route('modulos.show', $this->modulo));

        $this->assertDatabaseCount('leccion_user', 1);
        $this->assertSame(
            '2026-01-01 10:00:00',
            (string) DB::table('leccion_user')->where('leccion_id', $unica->id)->value('completada_en')
        );
    }

    public function test_inactive_lesson_is_not_found_for_a_worker(): void
    {
        $oculta = $this->leccion(['activa' => false]);

        $this->actingAs($this->trabajador)->get(route('lecciones.show', $oculta))->assertNotFound();
    }

    public function test_assigned_worker_can_view_and_download_the_pdf(): void
    {
        Storage::fake(config('filament.default_filesystem_disk'));
        Storage::disk(config('filament.default_filesystem_disk'))->put('lecciones/guia.pdf', '%PDF-1.4 contenido');
        $leccion = $this->leccion(['titulo' => 'Guia de ventas', 'archivo_pdf' => 'lecciones/guia.pdf']);

        $this->actingAs($this->trabajador);

        $this->get(route('lecciones.pdf', $leccion))->assertOk();
        $this->get(route('lecciones.pdf', ['leccion' => $leccion, 'descargar' => 1]))
            ->assertOk()
            ->assertDownload('guia-de-ventas.pdf');
    }

    public function test_missing_pdf_file_returns_not_found(): void
    {
        Storage::fake(config('filament.default_filesystem_disk'));
        $leccion = $this->leccion(['archivo_pdf' => 'lecciones/no-existe.pdf']);

        $this->actingAs($this->trabajador)->get(route('lecciones.pdf', $leccion))->assertNotFound();
    }
}
