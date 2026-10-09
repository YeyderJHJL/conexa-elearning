<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\User;
use App\View\Components\EncabezadoUsuario;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DisenoTrabajadorTest extends TestCase
{
    use RefreshDatabase;

    private function dom(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($documento);
    }

    public function test_initials_use_the_first_two_words_in_uppercase(): void
    {
        $this->assertSame('MD', EncabezadoUsuario::calcularIniciales('maría del Carmen'));
        $this->assertSame('J', EncabezadoUsuario::calcularIniciales('  jhamil '));
        $this->assertSame('?', EncabezadoUsuario::calcularIniciales('   '));
    }

    public function test_header_shows_name_initials_and_global_progress(): void
    {
        $trabajador = User::factory()->create(['rol' => 'trabajador', 'name' => 'Ana Pérez']);
        $area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);
        $trabajador->areas()->attach($area);
        $modulo = Modulo::create(['area_id' => $area->id, 'titulo' => 'Intro']);
        $trabajador->lecciones()->attach(Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'L1', 'activa' => true]));

        $html = $this->actingAs($trabajador)->get('/dashboard')->assertOk()->getContent();
        $xpath = $this->dom($html);

        $this->assertSame(1, $xpath->query('//*[@data-encabezado-usuario]')->length);
        $this->assertStringContainsString('AP', $xpath->query('//*[@data-encabezado-usuario]')->item(0)->textContent);

        // El nombre y el rol van al costado del botón, no dentro de él.
        $this->assertSame(0, $xpath->query('//nav//button[contains(., "Ana Pérez")]')->length);
        $costado = $xpath->query('//nav//*[@data-nombre-usuario]')->item(0);
        $this->assertStringContainsString('Ana Pérez', $costado->textContent);
        $this->assertStringContainsString('Colaborador', $costado->textContent);

        $anillo = $xpath->query('//nav//*[@role="progressbar"][@aria-label="Avance global"]');
        $this->assertSame(1, $anillo->length);
        $this->assertSame('100', $anillo->item(0)->getAttribute('aria-valuenow'));
    }

    public function test_account_menu_is_not_clipped_by_the_header(): void
    {
        $trabajador = User::factory()->create(['rol' => 'trabajador']);

        $xpath = $this->dom($this->actingAs($trabajador)->get('/dashboard')->assertOk()->getContent());

        $nav = $xpath->query('//nav[@aria-label="Principal"]')->item(0);
        $this->assertStringNotContainsString('overflow-hidden', $nav->getAttribute('class'), 'overflow-hidden en el nav recorta el menú desplegable.');
        $this->assertStringContainsString('z-30', $nav->getAttribute('class'));
        $this->assertSame(1, $xpath->query('//nav//button[@aria-label="Menú de cuenta"]')->length);
    }

    public function test_profile_page_shows_the_role_and_keeps_its_forms(): void
    {
        $colaborador = User::factory()->create(['rol' => 'trabajador', 'name' => 'Ana Pérez', 'cargo' => 'Vendedora']);

        $this->actingAs($colaborador)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Colaborador')
            ->assertSee('Vendedora')
            ->assertSee(route('profile.update'), false)
            ->assertSee(route('password.update'), false)
            ->assertSee(route('profile.destroy'), false);

        $admin = User::factory()->create(['rol' => 'admin']);
        $this->actingAs($admin)->get(route('profile.edit'))->assertOk()->assertSee('Administrador');
    }

    public function test_top_bar_is_simple_with_only_background_profile_and_logout_in_the_user_menu(): void
    {
        $trabajador = User::factory()->create(['rol' => 'trabajador', 'name' => 'Ana Pérez']);
        $otraArea = Area::create(['nombre' => 'Contabilidad secreta', 'slug' => 'contabilidad']);

        $html = $this->actingAs($trabajador)->get('/dashboard')->assertOk()->getContent();
        $xpath = $this->dom($html);

        $this->assertSame(0, $xpath->query('//aside')->length, 'No hay menú lateral.');
        $this->assertSame(0, $xpath->query('//nav//a[@href="'.route('dashboard').'"][not(.//img)]')->length, 'La barra solo tiene el logo como enlace al inicio.');
        $this->assertSame(0, $xpath->query('//nav//button[@aria-label="Menú"]')->length, 'No hay hamburguesa.');

        $menu = $xpath->query('//nav//*[@data-menu-cuenta]')->item(0);
        $this->assertNotNull($menu);
        $this->assertSame(1, $xpath->query('//*[@data-menu-cuenta]//*[@data-selector-fondo]')->length);
        $this->assertSame(2, $xpath->query('//*[@data-selector-fondo]//button')->length, 'Claro y oscuro.');
        $this->assertSame(1, $xpath->query('//*[@data-menu-cuenta]//a[@href="'.route('profile.edit').'"]')->length);
        $this->assertSame(1, $xpath->query('//*[@data-menu-cuenta]//form[@action="'.route('logout').'"]')->length);
        $this->assertStringNotContainsString('Contabilidad secreta', $menu->textContent);

        $this->assertStringContainsString("localStorage.getItem('tema') === 'oscuro'", $html, 'El fondo elegido se aplica antes de pintar.');
    }

    public function test_progress_ring_clamps_values_and_is_accessible(): void
    {
        $html = $this->renderizar('<x-anillo-progreso :valor="250" etiqueta="Avance X" />');
        $anillo = $this->dom($html)->query('//*[@role="progressbar"]')->item(0);

        $this->assertSame('100', $anillo->getAttribute('aria-valuenow'));
        $this->assertSame('Avance X', $anillo->getAttribute('aria-label'));

        $vacio = $this->dom($this->renderizar('<x-anillo-progreso :valor="-5" />'));
        $this->assertSame('0', $vacio->query('//*[@role="progressbar"]')->item(0)->getAttribute('aria-valuenow'));
        $this->assertSame(1, $vacio->query('//circle')->length, 'Con 0% solo se dibuja la pista.');
    }

    public function test_route_view_shows_a_banner_per_module_and_pulses_only_the_current_node(): void
    {
        $trabajador = User::factory()->create(['rol' => 'trabajador']);
        $area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);
        $trabajador->areas()->attach($area);
        $modulo = Modulo::create(['area_id' => $area->id, 'titulo' => 'Introduccion']);
        Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'Primera', 'activa' => true]);
        Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'Segunda', 'activa' => true]);

        $xpath = $this->dom(
            $this->actingAs($trabajador)->get(route('areas.show', ['area' => $area, 'vista' => 'ruta']))->assertOk()->getContent()
        );

        $this->assertSame(1, $xpath->query('//section[@data-tramo]//header')->length);

        $actual = $xpath->query('//*[@data-estado="actual"]');
        $this->assertSame(1, $actual->length);
        $this->assertStringContainsString('animate-pulso', $actual->item(0)->getElementsByTagName('a')->item(0)->getAttribute('class'));
        $this->assertSame(0, $xpath->query('//*[@data-estado="disponible" or @data-estado="bloqueado"]//a[contains(@class, "animate-pulso")]')->length);
    }

    public function test_quiz_options_are_selectable_cards_with_visually_hidden_radios(): void
    {
        $this->assertStringContainsString('has-[:checked]', file_get_contents(resource_path('views/quiz/show.blade.php')));
        $this->assertStringContainsString('peer sr-only', file_get_contents(resource_path('views/quiz/show.blade.php')));
    }

    private function renderizar(string $plantilla): string
    {
        return Blade::render($plantilla);
    }

    public function test_platform_identifier_and_titles_follow_conexa_e_learning(): void
    {
        $trabajador = User::factory()->create(['rol' => 'trabajador']);
        $area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);
        $trabajador->areas()->attach($area);

        $xpath = $this->dom($this->actingAs($trabajador)->get('/dashboard')->assertOk()->getContent());
        $this->assertStringContainsString('E-learning', $xpath->query('//nav//*[@data-plataforma]')->item(0)->textContent);

        $this->get(route('areas.show', $area))->assertSee('<title>Conexa E-learning — Ventas</title>', false);
        $this->get(route('profile.edit'))->assertSee('<title>Conexa E-learning — Mi perfil</title>', false);

        auth()->logout();
        $this->get('/login')->assertSee('<title>Conexa E-learning — Iniciar sesión</title>', false)->assertSee('data-plataforma', false);
    }
}
