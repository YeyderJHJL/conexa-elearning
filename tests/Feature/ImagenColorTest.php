<?php

namespace Tests\Feature;

use App\Filament\Resources\Areas\Pages\CreateArea;
use App\Filament\Resources\Modulos\Pages\CreateModulo;
use App\Models\Area;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImagenColorTest extends TestCase
{
    use RefreshDatabase;

    private User $trabajador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trabajador = User::factory()->create(['rol' => 'trabajador']);
    }

    private function area(array $atributos = []): Area
    {
        $area = Area::create($atributos + ['nombre' => 'Ventas', 'slug' => 'ventas', 'icono' => 'bi-cart']);
        $this->trabajador->areas()->attach($area);

        return $area;
    }

    private function modulo(Area $area, string $titulo, array $atributos = [], bool $vista = false): Modulo
    {
        $modulo = Modulo::create($atributos + ['area_id' => $area->id, 'titulo' => $titulo]);
        $leccion = Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'Leccion', 'activa' => true]);

        if ($vista) {
            $this->trabajador->lecciones()->attach($leccion);
        }

        return $modulo;
    }

    // ---------------------------------------------------------------- modelo

    public function test_brand_palette_constants(): void
    {
        $this->assertSame('#0E1A34', Area::COLOR_MARCA);
        $this->assertSame('#D7A743', Area::COLOR_DORADO);
        $this->assertSame('#4A6FA5', Area::COLOR_COMPLEMENTARIO);
    }

    public function test_accent_color_is_the_chosen_hex_or_the_brand_blue(): void
    {
        $this->assertSame('#4A6FA5', (new Area(['color' => '#4a6fa5']))->color_acento);
        $this->assertSame('#D7A743', (new Area(['color' => '#D7A743']))->color_acento);
        $this->assertSame(Area::COLOR_MARCA, (new Area)->color_acento);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function coloresInvalidos(): array
    {
        return [
            'vacio' => [''],
            'nombre' => ['red'],
            'corto' => ['#fff'],
            'sin almohadilla' => ['4A6FA5'],
            'no hex' => ['#GGGGGG'],
            'inyeccion css' => ['red;background:url(//evil.test/x)'],
            'inyeccion con hex' => ['#4A6FA5;background:url(//evil.test/x)'],
        ];
    }

    #[DataProvider('coloresInvalidos')]
    public function test_invalid_stored_colors_fall_back_to_the_brand_blue(string $color): void
    {
        $this->assertSame(Area::COLOR_MARCA, (new Area(['color' => $color]))->color_acento);
    }

    public function test_image_url_is_null_without_image_and_points_to_public_storage_with_one(): void
    {
        $this->assertNull((new Area)->imagen_url);
        $this->assertNull((new Modulo)->imagen_url);

        $this->assertStringEndsWith('/storage/areas/portada.jpg', (new Area(['imagen' => 'areas/portada.jpg']))->imagen_url);
        $this->assertStringEndsWith('/storage/modulos/m.png', (new Modulo(['imagen' => '/modulos/m.png']))->imagen_url);
    }

    // ------------------------------------------------ inicio: tarjetas de área

    public function test_area_card_without_image_or_color_uses_the_brand_fallback(): void
    {
        $this->area();

        $this->actingAs($this->trabajador)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('--acento: #0E1A34', false)
            ->assertSee('bi-cart')
            ->assertSee('rgba(215, 167, 67', false)
            ->assertDontSee('/storage/areas/');
    }

    public function test_area_card_uses_the_cover_image_and_the_accent_color(): void
    {
        $area = $this->area(['color' => '#4A6FA5', 'imagen' => 'areas/portada.jpg']);

        $this->actingAs($this->trabajador)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('--acento: #4A6FA5', false)
            ->assertSee('<img src="'.$area->imagen_url.'"', false);
    }

    public function test_an_invalid_stored_color_never_reaches_the_page(): void
    {
        // La columna mide 7 caracteres, así que como máximo cabe un valor corto e inválido.
        $this->area(['color' => '#ZZ;}x<', 'imagen' => null]);

        $this->actingAs($this->trabajador)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('--acento: #0E1A34', false)
            ->assertDontSee('#ZZ;}x', false);
    }

    // ------------------------------------------------- detalle del área y ruta

    public function test_area_page_shows_the_cover_and_sets_the_accent_for_its_modules(): void
    {
        $area = $this->area(['color' => '#D7A743', 'imagen' => 'areas/portada.jpg']);
        $this->modulo($area, 'Introduccion');

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', $area))
            ->assertOk()
            ->assertSee('<img src="'.$area->imagen_url.'"', false)
            ->assertSee('--acento: #D7A743', false);
    }

    public function test_module_row_shows_its_image_with_the_state_mark_and_grays_out_when_locked(): void
    {
        $area = $this->area();
        $actual = $this->modulo($area, 'Modulo actual', ['imagen' => 'modulos/actual.jpg', 'orden' => 1]);
        $bloqueado = $this->modulo($area, 'Modulo bloqueado', ['imagen' => 'modulos/bloqueado.jpg', 'orden' => 2]);

        $html = $this->actingAs($this->trabajador)
            ->get(route('areas.show', $area))
            ->assertOk()
            ->assertSee('<img src="'.$actual->imagen_url.'"', false)
            ->assertSee('<img src="'.$bloqueado->imagen_url.'"', false)
            ->getContent();

        $this->assertStringContainsString('grayscale', $html);
        $this->assertSame(1, substr_count($html, 'grayscale">') + substr_count($html, 'grayscale"'), 'Solo el módulo bloqueado va en gris.');
    }

    public function test_module_without_image_keeps_the_number_badge(): void
    {
        $area = $this->area();
        $this->modulo($area, 'Sin imagen');

        $this->actingAs($this->trabajador)
            ->get(route('areas.show', $area))
            ->assertOk()
            ->assertSee('Sin imagen')
            ->assertDontSee('/storage/modulos/');
    }

    // --------------------------------------------------------------- Filament

    public function test_area_form_saves_the_color_and_the_cover_image_on_the_public_disk(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['rol' => 'admin']));

        Livewire::test(CreateArea::class)
            ->assertFormFieldExists('color')
            ->assertFormFieldExists('imagen')
            ->fillForm([
                'nombre' => 'Seguridad',
                'slug' => 'seguridad',
                'orden' => 0,
                'activa' => true,
                'color' => '#4A6FA5',
                'imagen' => UploadedFile::fake()->create('portada.jpg', 50, 'image/jpeg'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $area = Area::where('slug', 'seguridad')->first();

        $this->assertSame('#4A6FA5', $area->color);
        $this->assertStringStartsWith('areas/', $area->imagen);
        Storage::disk('public')->assertExists($area->imagen);
    }

    public function test_area_color_and_image_are_optional(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'admin']));

        Livewire::test(CreateArea::class)
            ->fillForm(['nombre' => 'Basica', 'slug' => 'basica', 'orden' => 0, 'activa' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $area = Area::where('slug', 'basica')->first();

        $this->assertNull($area->color);
        $this->assertNull($area->imagen);
        $this->assertSame(Area::COLOR_MARCA, $area->color_acento);
    }

    public function test_area_form_offers_one_click_brand_colors(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'admin']));

        Livewire::test(CreateArea::class)
            ->callFormComponentAction('color', 'color_dorado')
            ->assertFormSet(['color' => Area::COLOR_DORADO])
            ->callFormComponentAction('color', 'color_azul_de_marca')
            ->assertFormSet(['color' => Area::COLOR_MARCA])
            ->callFormComponentAction('color', 'color_azul_complementario')
            ->assertFormSet(['color' => Area::COLOR_COMPLEMENTARIO]);
    }

    public function test_area_form_rejects_images_that_are_not_images(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['rol' => 'admin']));

        Livewire::test(CreateArea::class)
            ->fillForm([
                'nombre' => 'Invalida',
                'slug' => 'invalida',
                'orden' => 0,
                'activa' => true,
                'imagen' => UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasFormErrors(['imagen']);

        $this->assertDatabaseMissing('areas', ['slug' => 'invalida']);
    }

    public function test_module_form_saves_its_image_on_the_public_disk(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['rol' => 'admin']));
        $area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);

        Livewire::test(CreateModulo::class)
            ->assertFormFieldExists('imagen')
            ->fillForm([
                'area_id' => $area->id,
                'titulo' => 'Con imagen',
                'orden' => 0,
                'activo' => true,
                'imagen' => UploadedFile::fake()->create('modulo.png', 50, 'image/png'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $modulo = Modulo::where('titulo', 'Con imagen')->first();

        $this->assertStringStartsWith('modulos/', $modulo->imagen);
        Storage::disk('public')->assertExists($modulo->imagen);
    }

    public function test_module_image_is_optional_and_rejects_non_images(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['rol' => 'admin']));
        $area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);

        Livewire::test(CreateModulo::class)
            ->fillForm(['area_id' => $area->id, 'titulo' => 'Sin imagen', 'orden' => 0, 'activo' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNull(Modulo::where('titulo', 'Sin imagen')->first()->imagen);

        Livewire::test(CreateModulo::class)
            ->fillForm([
                'area_id' => $area->id,
                'titulo' => 'Invalido',
                'orden' => 0,
                'activo' => true,
                'imagen' => UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasFormErrors(['imagen']);
    }
}
