<?php

namespace Tests\Feature;

use App\Filament\Resources\Areas\AreaResource;
use App\Filament\Resources\Leccions\LeccionResource;
use App\Filament\Resources\Modulos\ModuloResource;
use App\Filament\Resources\Opcions\OpcionResource;
use App\Filament\Resources\Preguntas\PreguntaResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Area;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Navigation\NavigationGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class IdentidadMarcaTest extends TestCase
{
    use RefreshDatabase;

    private const LOGO_CLARO = 'Sobre%20fondo%20azul%20o%20negro.png';

    private const LOGO_OSCURO = 'Logo%20sobre%20fondo%20blanco.png';

    // ---------------------------------------------------------------- marca

    public function test_official_palette_uses_the_exact_hex_values(): void
    {
        $this->assertSame([
            'azul' => '#0E1A34',
            'dorado' => '#D7A743',
            'gris' => '#8C8C8E',
            'complementario' => '#4A6FA5',
            'profundo' => '#1F2F4A',
            'gris_claro' => '#B3B3B3',
        ], config('marca.colores'));

        $this->assertSame('Montserrat', config('marca.fuente'));
    }

    public function test_brand_logos_and_icons_exist_in_public(): void
    {
        foreach (config('marca.logos') as $archivo) {
            $this->assertFileExists(public_path('images/'.$archivo));
        }

        foreach (['favicon.ico', 'favicon.png', 'apple-touch-icon.png'] as $archivo) {
            $this->assertGreaterThan(0, filesize(public_path($archivo)), "{$archivo} no debe estar vacío");
        }

        $this->assertSame("\x00\x00\x01\x00", substr(File::get(public_path('favicon.ico')), 0, 4), 'favicon.ico debe ser un ICO válido');
    }

    public function test_tailwind_and_css_define_the_same_palette_as_the_config(): void
    {
        $tailwind = File::get(base_path('tailwind.config.js'));
        $css = File::get(resource_path('css/app.css'));

        foreach (config('marca.colores') as $hex) {
            $this->assertStringContainsString("'{$hex}'", $tailwind);
            $this->assertStringContainsStringIgnoringCase($hex, $css);
        }

        $this->assertStringContainsString("'Montserrat'", $tailwind);
        $this->assertStringContainsString('--marca-azul', $css);
        $this->assertStringContainsString('--marca-dorado', $css);
    }

    // -------------------------------------------------------------- Filament

    public function test_panel_uses_the_institutional_blue_as_primary_and_gold_as_secondary(): void
    {
        $colores = Filament::getPanel('admin')->getColors();

        $this->assertArrayHasKey('primary', $colores);
        $this->assertArrayHasKey('secondary', $colores);
        $this->assertSame('#0E1A34', config('marca.paletas.azul.600'));
        $this->assertSame('#D7A743', config('marca.paletas.dorado.500'));
        $this->assertCount(11, config('marca.paletas.azul'));
        $this->assertCount(11, config('marca.paletas.dorado'));
    }

    public function test_panel_uses_montserrat_logos_and_favicon(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertSame('Montserrat', $panel->getFontFamily());
        $this->assertSame(GoogleFontProvider::class, $panel->getFontProvider());
        $this->assertSame('Conexa Capital Central', (string) $panel->getBrandName());
        $this->assertStringEndsWith('/images/'.self::LOGO_OSCURO, $panel->getBrandLogo());
        $this->assertStringEndsWith('/images/'.self::LOGO_CLARO, $panel->getDarkModeBrandLogo());
        $this->assertSame('2.5rem', $panel->getBrandLogoHeight());
        $this->assertStringEndsWith('/favicon.png', $panel->getFavicon());
    }

    public function test_admin_login_renders_the_brand_logo_font_and_favicon(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee(self::LOGO_OSCURO, false)
            ->assertSee('fonts.googleapis.com', false)
            ->assertSee('Montserrat', false)
            ->assertSee('favicon.png', false);
    }

    public function test_menu_groups_are_ordered_and_resources_have_unique_icons_and_labels(): void
    {
        $grupos = collect(Filament::getPanel('admin')->getNavigationGroups())
            ->map(fn (NavigationGroup|string $grupo) => $grupo instanceof NavigationGroup ? $grupo->getLabel() : $grupo)
            ->all();

        $this->assertSame(['Contenido', 'Evaluación', 'Personas', 'Reportes'], $grupos);

        $esperado = [
            [AreaResource::class, 'Contenido', 'Áreas'],
            [ModuloResource::class, 'Contenido', 'Módulos'],
            [LeccionResource::class, 'Contenido', 'Lecciones'],
            [QuizResource::class, 'Evaluación', 'Quizzes'],
            [PreguntaResource::class, 'Evaluación', 'Preguntas'],
            [OpcionResource::class, 'Evaluación', 'Opciones'],
            [UserResource::class, 'Personas', 'Usuarios'],
        ];

        $iconos = [];

        foreach ($esperado as [$recurso, $grupo, $etiqueta]) {
            $this->assertSame($grupo, $recurso::getNavigationGroup(), $recurso);
            $this->assertSame($etiqueta, $recurso::getNavigationLabel(), $recurso);
            $iconos[] = $recurso::getNavigationIcon()->value;
        }

        $this->assertCount(count($esperado), array_unique($iconos), 'Cada recurso debe tener su propio ícono');
    }

    public function test_resources_are_sorted_inside_their_group(): void
    {
        $this->assertSame([1, 2, 3], [AreaResource::getNavigationSort(), ModuloResource::getNavigationSort(), LeccionResource::getNavigationSort()]);
        $this->assertSame([1, 2, 3], [QuizResource::getNavigationSort(), PreguntaResource::getNavigationSort(), OpcionResource::getNavigationSort()]);
    }

    // ---------------------------------------------------------- trabajador

    public function test_worker_layout_loads_montserrat_favicon_and_the_light_logo_on_the_blue_header(): void
    {
        $trabajador = User::factory()->create(['rol' => 'trabajador']);

        $this->actingAs($trabajador)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('family=Montserrat', false)
            ->assertSee('favicon.ico', false)
            ->assertSee('favicon.png', false)
            ->assertSee('apple-touch-icon.png', false)
            ->assertSee('<title>Conexa Capital Central</title>', false)
            ->assertSee('content="#0E1A34"', false)
            ->assertSee('bg-marca-azul', false)
            ->assertSee('images/'.self::LOGO_CLARO, false)
            ->assertDontSee('images/'.self::LOGO_OSCURO, false);
    }

    public function test_login_page_uses_the_dark_logo_on_a_light_background(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('family=Montserrat', false)
            ->assertSee('images/'.self::LOGO_OSCURO, false)
            ->assertDontSee('images/'.self::LOGO_CLARO, false)
            ->assertSee('favicon.png', false)
            ->assertSee('bg-marca-azul', false);
    }

    public function test_completed_state_and_progress_use_gold_and_percentages_stay_readable(): void
    {
        $trabajador = User::factory()->create(['rol' => 'trabajador']);
        $area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);
        $trabajador->areas()->attach($area);
        $modulo = Modulo::create(['area_id' => $area->id, 'titulo' => 'Introduccion']);
        $trabajador->lecciones()->attach(Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'Leccion', 'activa' => true]));

        $this->actingAs($trabajador);

        $this->get(route('areas.show', $area))
            ->assertOk()
            ->assertSee('Completado')
            ->assertSee('bg-marca-dorado/15', false)
            ->assertSee('bg-marca-dorado', false)
            ->assertSee('text-marca-azul', false);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('bg-marca-dorado', false)
            ->assertSee('text-marca-gris', false);
    }

    public function test_worker_views_no_longer_use_the_old_blue_or_indigo_palette(): void
    {
        $archivos = collect(File::allFiles(resource_path('views')))
            ->reject(fn ($archivo) => in_array($archivo->getFilename(), ['welcome.blade.php'], true)
                || str_contains($archivo->getPathname(), DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR)
                || str_contains($archivo->getPathname(), DIRECTORY_SEPARATOR.'filament'.DIRECTORY_SEPARATOR));

        $this->assertNotEmpty($archivos);

        foreach ($archivos as $archivo) {
            $this->assertDoesNotMatchRegularExpression(
                '/\b(?:bg|text|border|ring|from|to|via|fill|stroke|divide|outline|decoration)-(?:blue|indigo)-\d{2,3}/',
                File::get($archivo->getPathname()),
                "{$archivo->getRelativePathname()} todavía usa clases blue/indigo"
            );
        }
    }
}
