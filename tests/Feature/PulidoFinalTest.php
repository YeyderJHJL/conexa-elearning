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
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PulidoFinalTest extends TestCase
{
    use RefreshDatabase;

    private User $trabajador;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trabajador = User::factory()->create(['rol' => 'trabajador']);
        $this->area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);
        $this->trabajador->areas()->attach($this->area);
    }

    private function modulo(string $titulo = 'Introduccion', array $atributos = []): Modulo
    {
        return Modulo::create($atributos + ['area_id' => $this->area->id, 'titulo' => $titulo]);
    }

    private function leccion(Modulo $modulo, string $titulo, int $orden, bool $vista = false, array $atributos = []): Leccion
    {
        $leccion = Leccion::create($atributos + ['modulo_id' => $modulo->id, 'titulo' => $titulo, 'orden' => $orden, 'activa' => true]);

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

    /**
     * @return array<int, int>
     */
    private function respuestas(Quiz $quiz, bool $correctas): array
    {
        return $quiz->preguntas()->with('opciones')->get()
            ->mapWithKeys(fn (Pregunta $pregunta) => [$pregunta->id => $pregunta->opciones->firstWhere('es_correcta', $correctas)->id])
            ->all();
    }

    private function dom(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        @$documento->loadHTML('<?xml encoding="utf-8" ?>'.$html);

        return new DOMXPath($documento);
    }

    /**
     * Ratio de contraste WCAG 2.x entre dos colores hex (#RRGGBB).
     */
    private function contraste(string $primero, string $segundo): float
    {
        $luminancia = function (string $hex): float {
            [$r, $g, $b] = array_map(
                fn (string $canal) => ($c = hexdec($canal) / 255) <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4,
                str_split(ltrim($hex, '#'), 2)
            );

            return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        };

        $a = $luminancia($primero);
        $b = $luminancia($segundo);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    /**
     * Color resultante de poner $frente con opacidad $alfa sobre $fondo.
     */
    private function mezclar(string $frente, string $fondo, float $alfa): string
    {
        $mezcla = array_map(
            fn (string $f, string $b) => str_pad(dechex((int) round(hexdec($f) * $alfa + hexdec($b) * (1 - $alfa))), 2, '0', STR_PAD_LEFT),
            str_split(ltrim($frente, '#'), 2),
            str_split(ltrim($fondo, '#'), 2)
        );

        return '#'.implode('', $mezcla);
    }

    // ----------------------------------------------------------- celebración

    public function test_approving_the_quiz_that_completes_the_module_celebrates_once(): void
    {
        $modulo = $this->modulo('Introduccion a ventas');
        $this->leccion($modulo, 'Leccion', 1, vista: true);
        $quiz = $this->quiz($modulo);
        $this->actingAs($this->trabajador);

        $this->post(route('quiz.enviar', $modulo), ['respuestas' => $this->respuestas($quiz, true)])
            ->assertSessionHas('celebracion.titulo', '¡Módulo completado!');

        $intento = IntentoQuiz::first();

        $this->get(route('quiz.resultado', [$modulo, $intento]))
            ->assertOk()
            ->assertSee('data-celebracion', false)
            ->assertSee('¡Módulo completado!')
            ->assertSee('Introduccion a ventas')
            ->assertSee('role="status"', false)
            ->assertSee('aria-label="Cerrar mensaje"', false)
            ->assertSee('class="confeti"', false);

        $this->get(route('quiz.resultado', [$modulo, $intento]))
            ->assertOk()
            ->assertDontSee('data-celebracion', false)
            ->assertDontSee('class="confeti"', false);
    }

    public function test_the_confetti_is_decorative_and_has_eighteen_pieces(): void
    {
        $modulo = $this->modulo();
        $this->leccion($modulo, 'Leccion', 1, vista: true);
        $quiz = $this->quiz($modulo);
        $this->actingAs($this->trabajador)->post(route('quiz.enviar', $modulo), ['respuestas' => $this->respuestas($quiz, true)]);

        $html = $this->get(route('quiz.resultado', [$modulo, IntentoQuiz::first()]))->getContent();
        $xpath = $this->dom($html);

        $this->assertSame(1, $xpath->query('//div[contains(@class, "confeti")][@aria-hidden="true"]')->length);
        $this->assertSame(18, $xpath->query('//div[contains(@class, "confeti")]/span')->length);
    }

    public function test_a_failed_attempt_does_not_celebrate(): void
    {
        $modulo = $this->modulo();
        $this->leccion($modulo, 'Leccion', 1, vista: true);
        $quiz = $this->quiz($modulo);
        $this->actingAs($this->trabajador);

        $this->post(route('quiz.enviar', $modulo), ['respuestas' => $this->respuestas($quiz, false)])
            ->assertSessionMissing('celebracion');

        $this->get(route('quiz.resultado', [$modulo, IntentoQuiz::first()]))
            ->assertOk()
            ->assertDontSee('data-celebracion', false);
    }

    public function test_a_second_approved_attempt_celebrates_the_quiz_but_not_the_module_again(): void
    {
        $modulo = $this->modulo();
        $this->leccion($modulo, 'Leccion', 1, vista: true);
        $quiz = $this->quiz($modulo);
        $this->actingAs($this->trabajador);

        $this->post(route('quiz.enviar', $modulo), ['respuestas' => $this->respuestas($quiz, true)]);
        $this->post(route('quiz.enviar', $modulo), ['respuestas' => $this->respuestas($quiz, true)])
            ->assertSessionHas('celebracion.titulo', '¡Quiz aprobado!');
    }

    public function test_completing_the_last_lesson_of_a_module_without_quiz_celebrates_once(): void
    {
        $modulo = $this->modulo('Modulo sin quiz');
        $primera = $this->leccion($modulo, 'Primera', 1);
        $segunda = $this->leccion($modulo, 'Segunda', 2);
        $this->actingAs($this->trabajador);

        $this->post(route('lecciones.completar', $primera))->assertSessionMissing('celebracion');

        $this->post(route('lecciones.completar', $segunda))
            ->assertRedirect(route('modulos.show', $modulo))
            ->assertSessionHas('celebracion.titulo', '¡Módulo completado!');

        $this->get(route('modulos.show', $modulo))
            ->assertSee('data-celebracion', false)
            ->assertSee('Modulo sin quiz');

        $this->post(route('lecciones.completar', $segunda))->assertSessionMissing('celebracion');
    }

    public function test_completing_all_lessons_does_not_celebrate_while_the_quiz_is_pending(): void
    {
        $modulo = $this->modulo();
        $leccion = $this->leccion($modulo, 'Unica', 1);
        $this->quiz($modulo);

        $this->actingAs($this->trabajador)
            ->post(route('lecciones.completar', $leccion))
            ->assertSessionMissing('celebracion');
    }

    public function test_the_celebration_styles_respect_reduced_motion(): void
    {
        $css = File::get(resource_path('css/app.css'));

        $this->assertStringContainsString('.confeti', $css);
        $this->assertStringContainsString('@keyframes confeti-caida', $css);
        $this->assertMatchesRegularExpression('/prefers-reduced-motion:\s*reduce\)\s*\{\s*\.confeti\s*\{\s*display:\s*none/', $css);
        $this->assertStringContainsString('pointer-events: none', $css);
    }

    // --------------------------------------------------------- estados vacíos

    public function test_empty_states_are_friendly_everywhere(): void
    {
        $sinAreas = User::factory()->create(['rol' => 'trabajador']);
        $this->actingAs($sinAreas)->get('/dashboard')->assertOk()->assertSee('Aún no tienes áreas asignadas');

        $this->actingAs($this->trabajador);
        $this->get(route('areas.show', $this->area))->assertOk()->assertSee('Esta área aún no tiene módulos');

        $vacio = $this->modulo('Vacio');
        $this->get(route('modulos.show', $vacio))->assertOk()->assertSee('Este módulo aún no tiene lecciones');
    }

    public function test_a_lesson_without_content_shows_a_friendly_message(): void
    {
        $modulo = $this->modulo();
        $vacia = $this->leccion($modulo, 'Vacia', 1, atributos: ['contenido' => '<p></p>']);
        $conTexto = $this->leccion($modulo, 'Con texto', 2, atributos: ['contenido' => '<p>Hola</p>']);
        $conVideo = $this->leccion($modulo, 'Con video', 3, atributos: ['url_video' => 'https://youtu.be/dQw4w9WgXcQ']);
        $conImagen = $this->leccion($modulo, 'Con imagen', 4, atributos: ['contenido' => '<img src="x.png" alt="Diagrama">']);
        $this->actingAs($this->trabajador);

        $this->get(route('lecciones.show', $vacia))->assertOk()->assertSee('El contenido de esta lección llegará pronto');

        foreach ([$conTexto, $conVideo, $conImagen] as $leccion) {
            $this->get(route('lecciones.show', $leccion))->assertOk()->assertDontSee('El contenido de esta lección llegará pronto');
        }
    }

    public function test_the_route_view_has_a_friendly_empty_state(): void
    {
        $this->modulo('Sin lecciones');
        $this->actingAs($this->trabajador);

        $this->get(route('areas.show', ['area' => $this->area, 'vista' => 'ruta']))
            ->assertOk()
            ->assertSee('Tu ruta se está preparando')
            ->assertDontSee('aria-label="Leyenda"', false);

        $modulo = $this->modulo('Con lecciones');
        $this->leccion($modulo, 'Leccion', 1);

        $this->get(route('areas.show', ['area' => $this->area, 'vista' => 'ruta']))
            ->assertDontSee('Tu ruta se está preparando')
            ->assertSee('aria-label="Leyenda"', false);
    }

    // ---------------------------------------------------------- accesibilidad

    public function test_pages_declare_spanish_and_offer_a_skip_link_and_a_main_landmark(): void
    {
        $this->actingAs($this->trabajador)
            ->get('/dashboard')
            ->assertSee('<html lang="es">', false)
            ->assertSee('href="#contenido"', false)
            ->assertSee('Saltar al contenido')
            ->assertSee('<main id="contenido"', false)
            ->assertSee('aria-label="Principal"', false);

        auth()->logout();

        $this->get('/login')->assertOk()->assertSee('<html lang="es">', false);
    }

    public function test_the_current_page_is_announced_in_the_navigation_only_when_active(): void
    {
        $this->actingAs($this->trabajador);

        $dashboard = $this->get('/dashboard')->assertOk()->getContent();
        $xpath = $this->dom($dashboard);

        $this->assertSame(2, $xpath->query('//a[@aria-current="page"]')->length, 'Enlace de escritorio y del menú móvil.');
        $this->assertStringNotContainsString('($active', $dashboard);

        $perfil = $this->get(route('profile.edit'))->assertOk()->getContent();

        $this->assertSame(0, $this->dom($perfil)->query('//nav//a[@aria-current="page"]')->length);
        $this->assertStringNotContainsString('($active', $perfil);
    }

    public function test_progress_bars_have_an_accessible_name(): void
    {
        $modulo = $this->modulo('Introduccion');
        $this->leccion($modulo, 'Leccion', 1);
        $this->actingAs($this->trabajador);

        foreach (['/dashboard', route('areas.show', $this->area)] as $url) {
            $barras = $this->dom($this->get($url)->assertOk()->getContent())->query('//*[@role="progressbar"]');

            $this->assertGreaterThan(0, $barras->length, $url);

            foreach ($barras as $barra) {
                $this->assertNotSame('', trim($barra->getAttribute('aria-label')), "Barra sin nombre en {$url}");
            }
        }

        $this->get('/dashboard')->assertSee('aria-label="Avance global"', false)->assertSee('aria-label="Avance de Ventas"', false);
    }

    public function test_every_image_on_the_worker_pages_has_an_alt_attribute_and_logos_are_named(): void
    {
        $modulo = $this->modulo('Con imagen', ['imagen' => 'modulos/m.jpg']);
        $leccion = $this->leccion($modulo, 'Leccion', 1);
        $this->area->update(['imagen' => 'areas/a.jpg']);
        $this->actingAs($this->trabajador);

        $paginas = [
            '/dashboard',
            route('areas.show', $this->area),
            route('areas.show', ['area' => $this->area, 'vista' => 'ruta']),
            route('modulos.show', $modulo),
            route('lecciones.show', $leccion),
            route('profile.edit'),
        ];

        foreach ($paginas as $url) {
            $xpath = $this->dom($this->get($url)->assertOk()->getContent());

            $this->assertGreaterThan(0, $xpath->query('//img')->length, "Se esperaban imágenes en {$url}");
            $this->assertSame(0, $xpath->query('//img[not(@alt)]')->length, "Imagen sin alt en {$url}");
            $this->assertSame(0, $xpath->query('//iframe[not(@title)]')->length, "iframe sin title en {$url}");
        }

        $logo = $this->dom($this->get('/dashboard')->getContent())->query('//img[contains(@src, "Sobre%20fondo%20azul")]');
        $this->assertSame('Conexa Capital Central', $logo->item(0)->getAttribute('alt'));

        auth()->logout();

        $loginLogo = $this->dom($this->get('/login')->getContent())->query('//img[contains(@src, "Logo%20sobre%20fondo%20blanco")]');
        $this->assertSame('Conexa Capital Central', $loginLogo->item(0)->getAttribute('alt'));
    }

    public function test_gold_is_never_used_as_text_color_on_light_backgrounds(): void
    {
        $permitidos = ['components/ruta-nodo.blade.php', 'dashboard.blade.php'];
        $encontrados = [];

        foreach (File::allFiles(resource_path('views')) as $archivo) {
            $ruta = str_replace(DIRECTORY_SEPARATOR, '/', $archivo->getRelativePathname());

            if (in_array($ruta, ['welcome.blade.php'], true) || str_starts_with($ruta, 'pdf/') || str_starts_with($ruta, 'filament/')) {
                continue;
            }

            if (preg_match('/(?<![-\w])text-marca-dorado/', File::get($archivo->getPathname()))) {
                $encontrados[] = $ruta;
            }
        }

        sort($encontrados);

        $this->assertSame($permitidos, $encontrados, 'El dorado como color de texto solo se permite sobre fondo azul.');
    }

    public function test_small_text_uses_the_accessible_gray_and_focus_rings_are_dark_on_light_backgrounds(): void
    {
        $excluidos = ['welcome.blade.php', 'layouts/navigation.blade.php', 'components/nav-link.blade.php', 'components/responsive-nav-link.blade.php', 'components/celebracion.blade.php'];

        foreach (File::allFiles(resource_path('views')) as $archivo) {
            $ruta = str_replace(DIRECTORY_SEPARATOR, '/', $archivo->getRelativePathname());

            if (in_array($ruta, $excluidos, true) || str_starts_with($ruta, 'pdf/') || str_starts_with($ruta, 'filament/')) {
                continue;
            }

            $contenido = File::get($archivo->getPathname());

            $this->assertDoesNotMatchRegularExpression('/text-marca-gris(?![-\w])/', $contenido, "{$ruta}: usa el gris oficial en texto; usar text-marca-gris-texto");
            $this->assertDoesNotMatchRegularExpression('/focus(?:-visible)?:ring-marca-dorado/', $contenido, "{$ruta}: aro de foco dorado sobre fondo claro");
        }
    }

    public function test_global_focus_style_and_mobile_safeguards_exist_in_the_stylesheet(): void
    {
        $css = File::get(resource_path('css/app.css'));

        $this->assertStringContainsString(':focus-visible', $css);
        $this->assertStringContainsString('outline: 3px solid var(--marca-azul)', $css);
        $this->assertStringContainsString('.contenido-rico table', $css);
        $this->assertStringContainsString('overflow-x-auto', $css);
    }

    public function test_long_titles_wrap_and_touch_targets_and_inputs_are_mobile_friendly(): void
    {
        $modulo = $this->modulo(str_repeat('Largo', 20));
        $this->leccion($modulo, 'Leccion', 1, vista: true);
        $this->actingAs($this->trabajador);

        $this->get(route('areas.show', $this->area))->assertSee('break-words', false)->assertSee('min-h-10', false);

        $this->get(route('areas.encuesta', $this->area))
            ->assertOk()
            ->assertSee('text-base', false)
            ->assertSee('placeholder:text-marca-gris-texto', false);

        // La tarjeta "Continuar" solo existe si queda algo pendiente.
        $this->leccion($modulo, 'Pendiente', 2);

        $this->get('/dashboard')->assertSee('Continuar donde quedaste')->assertSee('line-clamp-2', false);
    }

    // --------------------------------------------------------------- contraste

    public function test_the_color_pairs_used_in_the_interface_meet_wcag_contrast(): void
    {
        $azul = config('marca.colores.azul');
        $dorado = config('marca.colores.dorado');
        $grisOficial = config('marca.colores.gris');
        $grisClaro = config('marca.colores.gris_claro');
        $profundo = config('marca.colores.profundo');
        $grisTexto = config('marca.derivados.gris_texto');
        $blanco = '#FFFFFF';
        $gris100 = '#F3F4F6';

        // Texto normal: mínimo AA 4,5:1
        $this->assertGreaterThanOrEqual(12, $this->contraste($azul, $blanco), 'Azul sobre blanco');
        $this->assertGreaterThanOrEqual(12, $this->contraste($blanco, $azul), 'Blanco sobre azul (encabezado)');
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($grisTexto, $blanco), 'Gris accesible sobre blanco');
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($grisTexto, $gris100), 'Gris accesible sobre gris 100');
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($azul, $dorado), 'Azul sobre dorado (insignias y botón Continuar)');
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($dorado, $azul), 'Dorado sobre azul (iconos del encabezado)');
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($grisClaro, $azul), 'Gris claro sobre azul (enlaces del menú)');
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($blanco, $profundo), 'Blanco sobre azul profundo (enlace activo móvil)');
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($azul, $this->mezclar($dorado, $blanco, 0.15)), 'Azul sobre dorado al 15 % (insignia Completado)');
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($azul, $this->mezclar($dorado, $blanco, 0.20)), 'Azul sobre dorado al 20 % (número de módulo completado)');
        $this->assertGreaterThanOrEqual(4.5, $this->contraste($profundo, $grisClaro), 'Candado sobre nodo bloqueado');

        // Componentes gráficos y foco: mínimo 3:1
        $this->assertGreaterThanOrEqual(3, $this->contraste($azul, $blanco), 'Aro de foco azul sobre blanco');
        $this->assertGreaterThanOrEqual(3, $this->contraste($dorado, $azul), 'Aro de foco dorado sobre el encabezado azul');
    }

    public function test_the_contrast_rules_explain_why_gold_and_the_official_gray_are_restricted(): void
    {
        $blanco = '#FFFFFF';

        $this->assertLessThan(3, $this->contraste(config('marca.colores.dorado'), $blanco), 'El dorado sobre blanco no sirve ni para aros: solo fondos y acentos.');
        $this->assertLessThan(4.5, $this->contraste(config('marca.colores.gris'), $blanco), 'El gris oficial no llega a AA en texto pequeño: por eso existe el derivado.');
        $this->assertGreaterThan($this->contraste(config('marca.colores.gris'), $blanco), $this->contraste(config('marca.derivados.gris_texto'), $blanco));
    }
}
