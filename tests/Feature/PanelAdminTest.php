<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ReporteAvance;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PanelAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['name' => 'Laura Mendoza', 'rol' => 'admin', 'cargo' => 'Jefa de capacitación']);
    }

    private function dom(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        @$documento->loadHTML('<?xml encoding="utf-8" ?>'.$html);

        return new DOMXPath($documento);
    }

    // ------------------------------------------------------------------- menú

    public function test_the_sidebar_has_exactly_four_groups_with_their_items_in_order(): void
    {
        $xpath = $this->dom($this->actingAs($this->admin)->get('/admin')->assertOk()->getContent());

        $grupos = [];

        foreach ($xpath->query('//li[@data-group-label and @data-group-label!=""]') as $grupo) {
            $etiquetas = [];

            foreach ($xpath->query('.//*[contains(@class, "fi-sidebar-item-label")]', $grupo) as $item) {
                $etiquetas[] = trim($item->textContent);
            }

            $grupos[$grupo->getAttribute('data-group-label')] = $etiquetas;
        }

        $this->assertSame([
            'Contenido' => ['Áreas', 'Módulos'],
            'Evaluación' => ['Quizzes'],
            'Personas' => ['Usuarios'],
            'Reportes' => ['Reporte de avance', 'Encuestas de feedback'],
        ], $grupos);
    }

    public function test_the_dashboard_is_the_only_item_outside_the_groups(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin')->assertOk()->getContent();
        $xpath = $this->dom($html);

        $inicio = '//*[contains(@class, "fi-sidebar-item-label")][normalize-space()="Inicio"]';

        $this->assertSame(1, $xpath->query($inicio)->length);
        $this->assertSame(1, $xpath->query($inicio.'/ancestor::li[@data-group-label=""]')->length, 'Filament agrupa lo que no tiene grupo en un bloque sin etiqueta.');
        $this->assertSame(0, $xpath->query($inicio.'/ancestor::li[@data-group-label!=""]')->length, 'Inicio no pertenece a ninguno de los 4 grupos.');
    }

    public function test_every_sidebar_item_has_its_own_icon(): void
    {
        $xpath = $this->dom($this->actingAs($this->admin)->get('/admin')->assertOk()->getContent());

        $items = $xpath->query('//li[contains(@class, "fi-sidebar-item")]');

        $this->assertGreaterThanOrEqual(7, $items->length, 'Inicio + 6 recursos y páginas.');

        foreach ($items as $item) {
            $this->assertGreaterThan(
                0,
                $xpath->query('.//svg', $item)->length,
                'Item de menú sin ícono: '.trim($item->textContent)
            );
        }
    }

    public function test_groups_are_collapsible_and_start_collapsed_without_native_icons(): void
    {
        $grupos = collect(Filament::getPanel('admin')->getNavigationGroups());

        $this->assertCount(4, $grupos);

        foreach ($grupos as $grupo) {
            $this->assertInstanceOf(NavigationGroup::class, $grupo);
            $this->assertTrue($grupo->isCollapsible(), $grupo->getLabel());
            $this->assertTrue($grupo->isCollapsed(), $grupo->getLabel());
            $this->assertNull($grupo->getIcon(), 'Filament no admite ícono en el grupo y en sus items a la vez.');
        }
    }

    public function test_group_icons_are_drawn_by_the_theme_for_exactly_the_panel_groups(): void
    {
        $css = File::get(resource_path('css/filament/admin/iconos-grupos.css'));
        preg_match_all('/data-group-label="([^"]+)"/', $css, $coincidencias);

        $delTema = collect($coincidencias[1])->unique()->values()->all();
        $delPanel = collect(Filament::getPanel('admin')->getNavigationGroups())->map(fn (NavigationGroup $grupo) => $grupo->getLabel())->all();

        $this->assertSame($delPanel, $delTema, 'Cada grupo del panel debe tener su ícono en iconos-grupos.css.');
        $this->assertSame(4, preg_match_all('/(?<!-webkit-)mask-image: url\("data:image\/svg\+xml/', $css), 'Un ícono por grupo.');
        $this->assertSame(4, preg_match_all('/-webkit-mask-image: url\("data:image\/svg\+xml/', $css), 'Y su variante con prefijo para Safari.');
        $this->assertStringContainsString('var(--conexa-dorado)', $css);
    }

    // ---------------------------------------------------------- barra superior

    public function test_topbar_shows_the_user_name_and_role_badge(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('data-topbar-usuario', false)
            ->assertSee('Laura Mendoza')
            ->assertSee('Jefa de capacitación')
            ->assertSee('Administrador');
    }

    public function test_role_labels_are_human_readable(): void
    {
        $this->assertSame('Administrador', $this->admin->etiquetaRol());
        $this->assertSame('Colaborador', User::factory()->make(['rol' => 'trabajador'])->etiquetaRol());
        $this->assertSame('Invitado', User::factory()->make(['rol' => 'invitado'])->etiquetaRol());
    }

    // -------------------------------------------------------------------- tema

    public function test_the_panel_loads_the_custom_theme_instead_of_the_default_stylesheet(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('build-admin/theme.css', false)
            ->assertDontSee('css/filament/filament/app.css', false);

        auth()->logout();

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('build-admin/theme.css', false)
            ->assertSee('fonts.googleapis.com', false);
    }

    public function test_panel_registers_our_dashboard_and_reports_pages_and_not_the_default_dashboard(): void
    {
        $paginas = Filament::getPanel('admin')->getPages();

        $this->assertContains(Dashboard::class, $paginas);
        $this->assertContains(ReporteAvance::class, $paginas);
        $this->assertNotContains(\Filament\Pages\Dashboard::class, $paginas);
    }

    public function test_palette_has_exact_brand_values_for_primary_secondary_and_info(): void
    {
        $colores = Filament::getPanel('admin')->getColors();

        $this->assertEqualsCanonicalizing(['primary', 'secondary', 'info'], array_intersect(['primary', 'secondary', 'info'], array_keys($colores)));
        $this->assertSame('#0E1A34', config('marca.paletas.azul.600'));
        $this->assertSame('#D7A743', config('marca.paletas.dorado.500'));
        $this->assertSame('#4A6FA5', config('marca.paletas.complementario.500'));
        $this->assertCount(11, config('marca.paletas.complementario'));
    }

    public function test_theme_source_applies_the_brand_palette_and_is_built_in_isolation_from_the_site(): void
    {
        $tema = File::get(resource_path('css/filament/admin/theme.css'));

        $this->assertStringContainsString("@import '../../../../vendor/filament/filament/resources/css/theme.css'", $tema);
        $this->assertStringContainsString('@source', $tema);

        foreach (['#0E1A34', '#D7A743', '#8C8C8E', '#4A6FA5'] as $hex) {
            $this->assertStringContainsStringIgnoringCase($hex, $tema);
        }

        foreach (['.fi-sidebar', '.fi-sidebar-item', '.fi-topbar', '.fi-wi-stats-overview-stat', '.fi-ta-header-cell'] as $clase) {
            $this->assertStringContainsString($clase, $tema);
        }

        $config = File::get(base_path('vite.admin.config.js'));
        $this->assertStringContainsString("outDir: 'public/build-admin'", $config);
        $this->assertStringContainsString('publicDir: false', $config);
        $this->assertStringContainsString("assetFileNames: 'theme.css'", $config);
    }

    public function test_building_the_theme_did_not_change_the_dependencies_of_the_site(): void
    {
        $paquete = json_decode(File::get(base_path('package.json')), true);

        $this->assertStringStartsWith('^3', $paquete['devDependencies']['tailwindcss'], 'El sitio sigue en Tailwind 3.');
        $this->assertSame('vite build --config vite.admin.config.js', $paquete['scripts']['build:admin']);
        $this->assertSame('vite build', $paquete['scripts']['build:site']);
        $this->assertStringContainsString('vite build --config vite.admin.config.js', $paquete['scripts']['build']);
        $this->assertStringContainsString('/public/build-admin', File::get(base_path('.gitignore')));
    }

    public function test_compiled_theme_contains_filament_base_styles_and_the_brand_rules(): void
    {
        $ruta = public_path('build-admin/theme.css');

        if (! is_file($ruta)) {
            $this->markTestSkipped('Falta compilar el tema: ejecuta `npm run build:admin`.');
        }

        $css = File::get($ruta);

        foreach (['--conexa-azul:#0e1a34', '--conexa-dorado:#d7a743', '.fi-sidebar{', 'fi-sidebar-group-label', 'data-group-label=Contenido', 'data-group-label=Reportes', 'mask-image:url'] as $esperado) {
            $this->assertStringContainsString($esperado, $css);
        }

        $this->assertGreaterThan(300_000, strlen($css), 'Debe incluir los estilos base de Filament, no solo los de marca.');
    }
}
