<?php

namespace Tests\Feature;

use App\Filament\Resources\Leccions\LeccionResource;
use App\Filament\Resources\Leccions\Pages\CreateLeccion;
use App\Filament\Resources\Leccions\Pages\EditLeccion;
use App\Filament\Resources\Leccions\Schemas\LeccionForm;
use App\Models\Area;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LeccionVideoTest extends TestCase
{
    use RefreshDatabase;

    private User $trabajador;

    private Area $area;

    private Modulo $modulo;

    private string $disco;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disco = config('filament.default_filesystem_disk');
        Storage::fake($this->disco);

        $this->trabajador = User::factory()->create(['rol' => 'trabajador']);
        $this->area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);
        $this->trabajador->areas()->attach($this->area);
        $this->modulo = Modulo::create(['area_id' => $this->area->id, 'titulo' => 'Modulo uno', 'orden' => 1]);
    }

    private function leccionConVideo(array $atributos = [], ?Modulo $modulo = null): Leccion
    {
        Storage::disk($this->disco)->put('lecciones/videos/clase.mp4', str_repeat('0123456789', 100));

        return Leccion::create($atributos + [
            'modulo_id' => ($modulo ?? $this->modulo)->id,
            'titulo' => 'Clase grabada',
            'activa' => true,
            'archivo_video' => 'lecciones/videos/clase.mp4',
        ]);
    }

    public function test_assigned_worker_streams_the_uploaded_video(): void
    {
        $leccion = $this->leccionConVideo();

        $this->actingAs($this->trabajador)
            ->get(route('lecciones.video', $leccion))
            ->assertOk()
            ->assertHeader('Content-Length', '1000');
    }

    public function test_video_supports_range_requests_so_the_player_can_seek(): void
    {
        $leccion = $this->leccionConVideo();

        $respuesta = $this->actingAs($this->trabajador)
            ->get(route('lecciones.video', $leccion), ['Range' => 'bytes=10-19']);

        $respuesta->assertStatus(206);
        $respuesta->assertHeader('Content-Range', 'bytes 10-19/1000');
    }

    public function test_guests_and_unassigned_workers_cannot_get_the_video(): void
    {
        $leccion = $this->leccionConVideo();

        $this->get(route('lecciones.video', $leccion))->assertRedirect('/login');

        $ajeno = User::factory()->create(['rol' => 'trabajador']);
        $this->actingAs($ajeno)->get(route('lecciones.video', $leccion))->assertForbidden();
    }

    public function test_inactive_lessons_hide_the_video_from_workers_but_not_from_admins(): void
    {
        $leccion = $this->leccionConVideo(['activa' => false]);

        $this->actingAs($this->trabajador)->get(route('lecciones.video', $leccion))->assertNotFound();

        $admin = User::factory()->create(['rol' => 'admin']);
        $this->actingAs($admin)->get(route('lecciones.video', $leccion))->assertOk();
    }

    public function test_missing_video_file_returns_not_found(): void
    {
        $leccion = Leccion::create([
            'modulo_id' => $this->modulo->id,
            'titulo' => 'Sin archivo',
            'activa' => true,
            'archivo_video' => 'lecciones/videos/no-existe.mp4',
        ]);

        $this->actingAs($this->trabajador)->get(route('lecciones.video', $leccion))->assertNotFound();
    }

    public function test_lesson_page_plays_the_uploaded_video_inside_the_platform(): void
    {
        $leccion = $this->leccionConVideo();

        $html = $this->actingAs($this->trabajador)->get(route('lecciones.show', $leccion))->assertOk()->getContent();

        $this->assertStringContainsString('<video', $html);
        $this->assertStringContainsString(route('lecciones.video', $leccion), $html);
        $this->assertStringContainsString('data-video-subido', $html);
        $this->assertStringNotContainsString('llegará pronto', $html, 'Un video subido cuenta como contenido.');
    }

    public function test_a_lesson_shows_only_one_video_source_even_with_legacy_data_in_both(): void
    {
        $leccion = $this->leccionConVideo(['url_video' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);

        $this->actingAs($this->trabajador)
            ->get(route('lecciones.show', $leccion))
            ->assertOk()
            ->assertSee('data-video-subido', false)
            ->assertDontSee('youtube-nocookie.com', false);
    }

    public function test_choosing_a_link_clears_the_uploaded_file_and_deletes_it_from_disk(): void
    {
        $leccion = $this->leccionConVideo();
        $this->actingAs(User::factory()->create(['rol' => 'admin']));

        Livewire::test(EditLeccion::class, ['record' => $leccion->getRouteKey()])
            ->assertFormSet(['tipo_video' => 'archivo'])
            ->fillForm(['tipo_video' => 'enlace', 'url_video' => 'https://youtu.be/dQw4w9WgXcQ'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(LeccionResource::getUrl('index'));

        $leccion->refresh();
        $this->assertNull($leccion->archivo_video);
        $this->assertSame('https://youtu.be/dQw4w9WgXcQ', $leccion->url_video);
        Storage::disk($this->disco)->assertMissing('lecciones/videos/clase.mp4');
    }

    public function test_choosing_a_file_or_no_video_clears_the_link(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'admin']));
        $leccion = Leccion::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Con enlace', 'activa' => true, 'url_video' => 'https://youtu.be/dQw4w9WgXcQ']);

        Livewire::test(EditLeccion::class, ['record' => $leccion->getRouteKey()])
            ->assertFormSet(['tipo_video' => 'enlace'])
            ->fillForm(['tipo_video' => 'ninguno'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($leccion->refresh()->url_video);
        $this->assertNull($leccion->archivo_video);
    }

    public function test_lessons_without_an_uploaded_video_render_no_player(): void
    {
        $leccion = Leccion::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Solo texto', 'contenido' => '<p>Hola</p>', 'activa' => true]);

        $this->actingAs($this->trabajador)
            ->get(route('lecciones.show', $leccion))
            ->assertOk()
            ->assertDontSee('<video', false);
    }

    public function test_admin_form_rejects_links_that_cannot_play_inside_the_platform(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'admin']));

        Livewire::test(CreateLeccion::class)
            ->fillForm(['modulo_id' => $this->modulo->id, 'titulo' => 'Enlace raro', 'orden' => 1, 'tipo_video' => 'enlace', 'url_video' => 'https://example.com/pagina-con-video'])
            ->call('create')
            ->assertHasFormErrors(['url_video']);

        foreach (['https://youtu.be/dQw4w9WgXcQ', 'https://vimeo.com/123456789', 'https://cdn.example.com/clase.mp4'] as $i => $enlace) {
            Livewire::test(CreateLeccion::class)
                ->fillForm(['modulo_id' => $this->modulo->id, 'titulo' => "Enlace válido {$i}", 'orden' => 1, 'tipo_video' => 'enlace', 'url_video' => $enlace])
                ->call('create')
                ->assertHasNoFormErrors();
        }
    }

    public function test_upload_limit_shown_in_the_form_follows_the_real_php_limits(): void
    {
        $limite = LeccionForm::limiteDeSubidaEnMb();

        $this->assertGreaterThanOrEqual(1, $limite);
        $this->assertLessThanOrEqual(200, $limite);
        $this->assertLessThanOrEqual(
            (int) ini_get('upload_max_filesize') ?: 200,
            $limite,
            'El límite mostrado no puede superar el de PHP.',
        );
    }

    public function test_admin_form_stores_an_uploaded_video_and_livewire_allows_large_files(): void
    {
        $this->assertContains('max:204800', config('livewire.temporary_file_upload.rules'), 'Los videos de hasta 200 MB deben pasar la subida temporal de Livewire.');

        $this->actingAs(User::factory()->create(['rol' => 'admin']));

        $archivo = UploadedFile::fake()->create('bienvenida.mp4', 2048, 'video/mp4');

        Livewire::test(CreateLeccion::class)
            ->fillForm([
                'modulo_id' => $this->modulo->id,
                'titulo' => 'Bienvenida',
                'orden' => 1,
                'tipo_video' => 'archivo',
                'archivo_video' => $archivo,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $leccion = Leccion::where('titulo', 'Bienvenida')->firstOrFail();

        $this->assertNotNull($leccion->archivo_video);
        $this->assertStringStartsWith('lecciones/videos/', $leccion->archivo_video);
        Storage::disk($this->disco)->assertExists($leccion->archivo_video);
    }
}
