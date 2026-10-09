<?php

namespace Tests\Feature;

use App\Filament\Resources\Areas\Pages\ListAreas;
use App\Filament\Resources\Leccions\Pages\ListLeccions;
use App\Filament\Resources\Modulos\Pages\ListModulos;
use App\Filament\Resources\Opcions\Pages\ListOpcions;
use App\Filament\Resources\Preguntas\Pages\ListPreguntas;
use App\Filament\Resources\Quizzes\Pages\ListQuizzes;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Area;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TablasAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Area $ventas;

    private Area $archivada;

    private Modulo $intro;

    private Modulo $antiguo;

    private Modulo $avanzado;

    private Leccion $conVideo;

    private Leccion $sinNada;

    private Leccion $oculta;

    private Quiz $quizIntro;

    private Quiz $quizAvanzado;

    private Pregunta $preguntaIntro;

    private Pregunta $preguntaAvanzado;

    private Opcion $correcta;

    private Opcion $incorrecta;

    private Opcion $incorrectaAvanzado;

    private User $ana;

    private User $pedro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['name' => 'Laura', 'rol' => 'admin']);

        $this->ventas = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas', 'orden' => 1, 'color' => '#D7A743', 'resumen_pdf' => 'resumenes/a.pdf']);
        $this->archivada = Area::create(['nombre' => 'Archivada', 'slug' => 'archivada', 'orden' => 2, 'activa' => false]);

        $this->intro = Modulo::create(['area_id' => $this->ventas->id, 'titulo' => 'Introduccion', 'orden' => 1]);
        $this->antiguo = Modulo::create(['area_id' => $this->archivada->id, 'titulo' => 'Antiguo', 'orden' => 2, 'activo' => false]);
        $this->avanzado = Modulo::create(['area_id' => $this->ventas->id, 'titulo' => 'Avanzado', 'orden' => 3]);

        $this->conVideo = Leccion::create(['modulo_id' => $this->intro->id, 'titulo' => 'Con video', 'orden' => 1, 'url_video' => 'https://youtu.be/dQw4w9WgXcQ', 'archivo_pdf' => 'lecciones/g.pdf', 'duracion_min' => 12]);
        $this->sinNada = Leccion::create(['modulo_id' => $this->intro->id, 'titulo' => 'Solo texto', 'orden' => 2, 'activa' => true]);
        $this->oculta = Leccion::create(['modulo_id' => $this->antiguo->id, 'titulo' => 'Oculta', 'orden' => 1, 'activa' => false]);

        $this->quizIntro = Quiz::create(['modulo_id' => $this->intro->id, 'titulo' => 'Quiz intro']);
        $this->quizAvanzado = Quiz::create(['modulo_id' => $this->avanzado->id, 'titulo' => 'Quiz avanzado', 'nota_minima' => 80]);

        $this->preguntaIntro = Pregunta::create(['quiz_id' => $this->quizIntro->id, 'enunciado' => 'Cual es la capital?', 'orden' => 1]);
        $this->preguntaAvanzado = Pregunta::create(['quiz_id' => $this->quizAvanzado->id, 'enunciado' => 'Pregunta avanzada', 'orden' => 1]);

        $this->correcta = Opcion::create(['pregunta_id' => $this->preguntaIntro->id, 'texto' => 'Lima', 'es_correcta' => true]);
        $this->incorrecta = Opcion::create(['pregunta_id' => $this->preguntaIntro->id, 'texto' => 'Quito', 'es_correcta' => false]);
        $this->incorrectaAvanzado = Opcion::create(['pregunta_id' => $this->preguntaAvanzado->id, 'texto' => 'Opcion avanzada', 'es_correcta' => false]);

        $this->ana = User::factory()->create(['name' => 'Ana', 'rol' => 'trabajador', 'cargo' => 'Asesora']);
        $this->ana->areas()->attach($this->ventas);
        $this->pedro = User::factory()->create(['name' => 'Pedro', 'rol' => 'trabajador', 'activo' => false]);
    }

    private function lista(string $pagina)
    {
        return Livewire::actingAs($this->admin)->test($pagina);
    }

    // ------------------------------------------------------------------ áreas

    public function test_areas_table_shows_status_badges_counts_and_filters_by_state(): void
    {
        $this->lista(ListAreas::class)
            ->assertCanSeeTableRecords([$this->ventas, $this->archivada])
            ->assertTableColumnFormattedStateSet('activa', 'Activa', $this->ventas)
            ->assertTableColumnFormattedStateSet('activa', 'Inactiva', $this->archivada)
            ->assertSee('Activa')
            ->assertSee('Inactiva')
            ->assertTableColumnStateSet('modulos_count', 2, $this->ventas)
            ->assertTableColumnStateSet('usuarios_count', 1, $this->ventas)
            ->assertTableColumnStateSet('color', '#D7A743', $this->ventas)
            ->assertTableColumnStateSet('resumen_pdf', 'resumenes/a.pdf', $this->ventas)
            ->filterTable('activa', true)
            ->assertCanSeeTableRecords([$this->ventas])
            ->assertCanNotSeeTableRecords([$this->archivada])
            ->filterTable('activa', false)
            ->assertCanSeeTableRecords([$this->archivada])
            ->assertCanNotSeeTableRecords([$this->ventas]);
    }

    // ---------------------------------------------------------------- módulos

    public function test_modules_table_shows_badges_and_filters_by_area_and_state(): void
    {
        $this->lista(ListModulos::class)
            ->assertCanSeeTableRecords([$this->intro, $this->antiguo, $this->avanzado])
            ->assertTableColumnFormattedStateSet('activo', 'Activo', $this->intro)
            ->assertTableColumnFormattedStateSet('activo', 'Inactivo', $this->antiguo)
            ->assertTableColumnFormattedStateSet('quiz_count', 'Con quiz', $this->intro)
            ->assertTableColumnFormattedStateSet('quiz_count', 'Sin quiz', $this->antiguo)
            ->assertSee('Con quiz')
            ->assertSee('Sin quiz')
            ->assertSee('Inactivo')
            ->assertTableColumnStateSet('lecciones_count', 2, $this->intro)
            ->assertTableColumnStateSet('area.nombre', 'Ventas', $this->intro)
            ->filterTable('area_id', $this->ventas->id)
            ->assertCanSeeTableRecords([$this->intro, $this->avanzado])
            ->assertCanNotSeeTableRecords([$this->antiguo])
            ->removeTableFilter('area_id')
            ->filterTable('activo', false)
            ->assertCanSeeTableRecords([$this->antiguo])
            ->assertCanNotSeeTableRecords([$this->intro, $this->avanzado]);
    }

    // --------------------------------------------------------------- lecciones

    public function test_lessons_table_shows_resource_icons_and_filters_by_area_module_and_state(): void
    {
        $this->lista(ListLeccions::class)
            ->assertCanSeeTableRecords([$this->conVideo, $this->sinNada, $this->oculta])
            ->assertTableColumnStateSet('url_video', 'https://youtu.be/dQw4w9WgXcQ', $this->conVideo)
            ->assertTableColumnStateSet('archivo_pdf', 'lecciones/g.pdf', $this->conVideo)
            ->assertTableColumnStateSet('url_video', null, $this->sinNada)
            ->assertTableColumnStateSet('duracion_min', 12, $this->conVideo)
            ->assertTableColumnFormattedStateSet('activa', 'Activa', $this->conVideo)
            ->assertTableColumnFormattedStateSet('activa', 'Inactiva', $this->oculta)
            ->assertSee('Inactiva')
            ->filterTable('area', $this->ventas->id)
            ->assertCanSeeTableRecords([$this->conVideo, $this->sinNada])
            ->assertCanNotSeeTableRecords([$this->oculta])
            ->removeTableFilter('area')
            ->filterTable('modulo_id', $this->antiguo->id)
            ->assertCanSeeTableRecords([$this->oculta])
            ->assertCanNotSeeTableRecords([$this->conVideo, $this->sinNada])
            ->removeTableFilter('modulo_id')
            ->filterTable('activa', false)
            ->assertCanSeeTableRecords([$this->oculta])
            ->assertCanNotSeeTableRecords([$this->conVideo]);
    }

    // ----------------------------------------------------------------- quizzes

    public function test_quizzes_table_shows_minimum_grade_default_and_filters(): void
    {
        $this->lista(ListQuizzes::class)
            ->assertCanSeeTableRecords([$this->quizIntro, $this->quizAvanzado])
            ->assertTableColumnFormattedStateSet('nota_minima', '70% (por defecto)', $this->quizIntro)
            ->assertTableColumnFormattedStateSet('nota_minima', '80%', $this->quizAvanzado)
            ->assertTableColumnStateSet('preguntas_count', 1, $this->quizIntro)
            ->assertSee('70% (por defecto)')
            ->filterTable('modulo_id', $this->avanzado->id)
            ->assertCanSeeTableRecords([$this->quizAvanzado])
            ->assertCanNotSeeTableRecords([$this->quizIntro])
            ->removeTableFilter('modulo_id')
            ->filterTable('area', $this->ventas->id)
            ->assertCanSeeTableRecords([$this->quizIntro, $this->quizAvanzado]);
    }

    // --------------------------------------------------------------- preguntas

    public function test_questions_table_counts_options_and_filters_by_quiz(): void
    {
        $this->lista(ListPreguntas::class)
            ->assertCanSeeTableRecords([$this->preguntaIntro, $this->preguntaAvanzado])
            ->assertTableColumnStateSet('opciones_count', 2, $this->preguntaIntro)
            ->assertTableColumnStateSet('quiz.titulo', 'Quiz intro', $this->preguntaIntro)
            ->filterTable('quiz_id', $this->quizAvanzado->id)
            ->assertCanSeeTableRecords([$this->preguntaAvanzado])
            ->assertCanNotSeeTableRecords([$this->preguntaIntro]);
    }

    // ---------------------------------------------------------------- opciones

    public function test_options_table_marks_correct_answers_and_shows_the_question_text(): void
    {
        $this->lista(ListOpcions::class)
            ->assertCanSeeTableRecords([$this->correcta, $this->incorrecta, $this->incorrectaAvanzado])
            ->assertTableColumnFormattedStateSet('es_correcta', 'Correcta', $this->correcta)
            ->assertTableColumnFormattedStateSet('es_correcta', 'Incorrecta', $this->incorrecta)
            ->assertSee('Incorrecta')
            ->assertSee('Cual es la capital?')
            ->assertTableColumnStateSet('pregunta.enunciado', 'Cual es la capital?', $this->correcta)
            ->filterTable('es_correcta', true)
            ->assertCanSeeTableRecords([$this->correcta])
            ->assertCanNotSeeTableRecords([$this->incorrecta, $this->incorrectaAvanzado])
            ->removeTableFilter('es_correcta')
            ->filterTable('quiz', $this->quizAvanzado->id)
            ->assertCanSeeTableRecords([$this->incorrectaAvanzado])
            ->assertCanNotSeeTableRecords([$this->correcta, $this->incorrecta]);
    }

    // ---------------------------------------------------------------- usuarios

    public function test_users_table_shows_role_and_state_badges_and_filters(): void
    {
        $this->lista(ListUsers::class)
            ->assertCanSeeTableRecords([$this->admin, $this->ana, $this->pedro])
            ->assertTableColumnFormattedStateSet('rol', 'Administrador', $this->admin)
            ->assertTableColumnFormattedStateSet('rol', 'Colaborador', $this->ana)
            ->assertTableColumnFormattedStateSet('activo', 'Activo', $this->ana)
            ->assertTableColumnFormattedStateSet('activo', 'Inactivo', $this->pedro)
            ->assertSee('Administrador')
            ->assertSee('Colaborador')
            ->assertSee('Inactivo')
            ->assertTableColumnStateSet('areas_count', 1, $this->ana)
            ->filterTable('rol', 'trabajador')
            ->assertCanSeeTableRecords([$this->ana, $this->pedro])
            ->assertCanNotSeeTableRecords([$this->admin])
            ->removeTableFilter('rol')
            ->filterTable('activo', false)
            ->assertCanSeeTableRecords([$this->pedro])
            ->assertCanNotSeeTableRecords([$this->ana, $this->admin])
            ->removeTableFilter('activo')
            ->filterTable('area', $this->ventas->id)
            ->assertCanSeeTableRecords([$this->ana])
            ->assertCanNotSeeTableRecords([$this->admin, $this->pedro]);
    }

    public function test_users_can_still_be_searched_by_name_and_email(): void
    {
        $this->lista(ListUsers::class)
            ->searchTable('Ana')
            ->assertCanSeeTableRecords([$this->ana])
            ->assertCanNotSeeTableRecords([$this->pedro])
            ->searchTable($this->pedro->email)
            ->assertCanSeeTableRecords([$this->pedro])
            ->assertCanNotSeeTableRecords([$this->ana]);
    }

    public function test_every_resource_list_keeps_its_edit_action_and_delete_bulk_action(): void
    {
        $paginas = [
            ListAreas::class => $this->ventas,
            ListModulos::class => $this->intro,
            ListLeccions::class => $this->conVideo,
            ListQuizzes::class => $this->quizIntro,
            ListPreguntas::class => $this->preguntaIntro,
            ListOpcions::class => $this->correcta,
            ListUsers::class => $this->ana,
        ];

        foreach ($paginas as $pagina => $registro) {
            $this->lista($pagina)
                ->assertTableActionExists('edit', record: $registro)
                ->assertTableBulkActionExists('delete');
        }
    }

    public function test_theme_defines_a_single_variable_for_every_table_header(): void
    {
        $css = file_get_contents(resource_path('css/filament/admin/theme.css'));

        $this->assertSame(1, preg_match_all('/--conexa-table-head:\s*#0E1A34;/i', $css), 'La variable se define una sola vez, en :root.');
        $this->assertStringContainsString('.fi-ta-table > thead > tr', $css);
        $this->assertStringContainsString('background-color: var(--conexa-table-head);', $css);
        $this->assertSame(0, preg_match('/thead[^{]*\{[^}]*background-color:\s*#/i', $css), 'La cabecera no debe llevar colores sueltos.');

        $luminancia = fn (string $hex) => array_sum(array_map(
            fn ($c, $peso) => $peso * ((($v = hexdec($c) / 255) <= 0.03928) ? $v / 12.92 : ((($v + 0.055) / 1.055) ** 2.4)),
            str_split(ltrim($hex, '#'), 2),
            [0.2126, 0.7152, 0.0722],
        ));
        $claro = max($luminancia('#ffffff'), $luminancia('#0E1A34'));
        $oscuro = min($luminancia('#ffffff'), $luminancia('#0E1A34'));
        $this->assertGreaterThanOrEqual(4.5, ($claro + 0.05) / ($oscuro + 0.05), 'Texto de cabecera ilegible.');
    }

    public function test_no_other_rule_paints_a_header_cell_background(): void
    {
        $css = file_get_contents(resource_path('css/filament/admin/theme.css'));

        preg_match_all('/([^{}]*header-cell[^{}]*)\{([^{}]*)\}/', $css, $reglas, PREG_SET_ORDER);

        foreach ($reglas as [$todo, $selector, $cuerpo]) {
            $this->assertDoesNotMatchRegularExpression('/background(-color)?\s*:/', $cuerpo, 'Una regla pinta la celda de cabecera y tapa el color estándar: '.trim($selector));
        }

        $this->assertStringNotContainsString('#eef1f7', $css);
    }
}
