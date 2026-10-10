<?php

namespace Tests\Feature;

use App\Filament\Resources\Preguntas\Pages\CreatePregunta;
use App\Filament\Resources\Preguntas\Pages\EditPregunta;
use App\Filament\Resources\Quizzes\Pages\CreateQuiz;
use App\Filament\Resources\Quizzes\Pages\EditQuiz;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Filament\Resources\Quizzes\RelationManagers\PreguntasRelationManager;
use App\Models\Area;
use App\Models\Modulo;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Quiz;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class QuizFormularioTest extends TestCase
{
    use RefreshDatabase;

    private Modulo $modulo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['rol' => 'admin']));

        $area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);
        $this->modulo = Modulo::create(['area_id' => $area->id, 'titulo' => 'Introducción', 'orden' => 1]);
    }

    /**
     * @return array<string, mixed>
     */
    private function pregunta(string $enunciado, int $correcta = 0): array
    {
        return [
            'enunciado' => $enunciado,
            'opciones' => [
                ['texto' => 'Opción A', 'es_correcta' => $correcta === 0],
                ['texto' => 'Opción B', 'es_correcta' => $correcta === 1],
            ],
        ];
    }

    private function gestor(Quiz $quiz): Testable
    {
        return Livewire::test(PreguntasRelationManager::class, [
            'ownerRecord' => $quiz,
            'pageClass' => EditQuiz::class,
        ]);
    }

    public function test_the_edit_page_shows_the_data_and_questions_tabs(): void
    {
        $quiz = Quiz::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Quiz']);

        $this->get(QuizResource::getUrl('edit', ['record' => $quiz]))
            ->assertOk()
            ->assertSee('Datos del quiz')
            ->assertSee('Preguntas');
    }

    public function test_creating_a_quiz_opens_its_edit_page_to_add_questions(): void
    {
        Livewire::test(CreateQuiz::class)
            ->fillForm(['modulo_id' => $this->modulo->id, 'titulo' => 'Quiz de inducción', 'nota_minima' => 80])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(QuizResource::getUrl('edit', ['record' => Quiz::firstOrFail()]));
    }

    public function test_a_module_cannot_get_a_second_quiz(): void
    {
        Quiz::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Primero']);

        Livewire::test(CreateQuiz::class)
            ->fillForm(['modulo_id' => $this->modulo->id, 'titulo' => 'Segundo'])
            ->call('create')
            ->assertHasFormErrors(['modulo_id' => 'unique']);
    }

    public function test_a_question_is_created_from_the_modal_with_its_options_and_next_order(): void
    {
        $quiz = Quiz::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Quiz']);
        Pregunta::create(['quiz_id' => $quiz->id, 'enunciado' => 'Anterior', 'orden' => 3]);

        $this->gestor($quiz)
            ->callAction(TestAction::make(CreateAction::class)->table(), data: $this->pregunta('¿Nueva?', 1))
            ->assertHasNoFormErrors();

        $pregunta = Pregunta::where('enunciado', '¿Nueva?')->firstOrFail();

        $this->assertSame($quiz->id, $pregunta->quiz_id);
        $this->assertSame(4, $pregunta->orden);
        $this->assertSame(['Opción B'], $pregunta->opciones()->where('es_correcta', true)->pluck('texto')->all());
    }

    public function test_each_question_needs_one_correct_option_and_at_least_two_options(): void
    {
        $quiz = Quiz::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Quiz']);

        $sinCorrecta = ['enunciado' => '¿Sin correcta?', 'opciones' => [
            ['texto' => 'A', 'es_correcta' => false],
            ['texto' => 'B', 'es_correcta' => false],
        ]];
        $unaOpcion = ['enunciado' => '¿Una?', 'opciones' => [
            ['texto' => 'A', 'es_correcta' => true],
        ]];

        foreach ([$sinCorrecta, $unaOpcion] as $datos) {
            $this->gestor($quiz)
                ->callAction(TestAction::make(CreateAction::class)->table(), data: $datos)
                ->assertHasFormErrors();
        }

        $this->assertSame(0, Pregunta::count());
    }

    public function test_marking_an_option_as_correct_unmarks_the_others(): void
    {
        $componente = Livewire::test(CreatePregunta::class)
            ->fillForm($this->pregunta('¿Una sola?'));

        [$primera, $segunda] = array_keys($componente->get('data.opciones'));

        $componente->set("data.opciones.{$segunda}.es_correcta", true);

        $this->assertFalse($componente->get("data.opciones.{$primera}.es_correcta"));
        $this->assertTrue($componente->get("data.opciones.{$segunda}.es_correcta"));
    }

    public function test_editing_a_question_from_the_modal_keeps_its_options_by_id(): void
    {
        $quiz = Quiz::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Quiz']);
        $pregunta = Pregunta::create(['quiz_id' => $quiz->id, 'enunciado' => 'Original', 'orden' => 1]);
        $correcta = Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'A', 'es_correcta' => true]);
        Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'B', 'es_correcta' => false]);

        $this->gestor($quiz)
            ->callAction(TestAction::make(EditAction::class)->table($pregunta), data: ['enunciado' => 'Editada'])
            ->assertHasNoFormErrors();

        $this->assertSame('Editada', $pregunta->fresh()->enunciado);
        $this->assertSame(1, Pregunta::count());
        $this->assertSame(2, Opcion::count());
        $this->assertTrue($correcta->fresh()->es_correcta);
    }

    public function test_the_table_lists_only_the_questions_of_its_quiz_with_the_correct_answer(): void
    {
        $quiz = Quiz::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Quiz']);
        $propia = Pregunta::create(['quiz_id' => $quiz->id, 'enunciado' => 'Propia', 'orden' => 1]);
        Opcion::create(['pregunta_id' => $propia->id, 'texto' => 'Sí', 'es_correcta' => true]);
        Opcion::create(['pregunta_id' => $propia->id, 'texto' => 'No', 'es_correcta' => false]);

        $otroModulo = Modulo::create(['area_id' => $this->modulo->area_id, 'titulo' => 'Otro', 'orden' => 2]);
        $otroQuiz = Quiz::create(['modulo_id' => $otroModulo->id, 'titulo' => 'Otro quiz']);
        $ajena = Pregunta::create(['quiz_id' => $otroQuiz->id, 'enunciado' => 'Ajena', 'orden' => 1]);

        $this->gestor($quiz)
            ->assertCanSeeTableRecords([$propia])
            ->assertCanNotSeeTableRecords([$ajena])
            ->assertTableColumnStateSet('opciones_count', 2, $propia)
            ->assertTableColumnStateSet('correcta', 'Sí', $propia);
    }

    public function test_a_question_is_created_with_its_options_from_its_own_page(): void
    {
        $quiz = Quiz::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Quiz']);

        Livewire::test(CreatePregunta::class)
            ->fillForm(['quiz_id' => $quiz->id, 'orden' => 1, ...$this->pregunta('¿Suelta?', 1)])
            ->call('create')
            ->assertHasNoFormErrors();

        $pregunta = Pregunta::firstOrFail();

        $this->assertSame(2, $pregunta->opciones()->count());
        $this->assertSame(['Opción B'], $pregunta->opciones()->where('es_correcta', true)->pluck('texto')->all());
    }

    public function test_options_can_be_added_when_editing_a_question_on_its_own_page(): void
    {
        $quiz = Quiz::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Quiz']);
        $pregunta = Pregunta::create(['quiz_id' => $quiz->id, 'enunciado' => 'Original', 'orden' => 1]);
        $existente = Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'A', 'es_correcta' => true]);

        $componente = Livewire::test(EditPregunta::class, ['record' => $pregunta->getKey()]);
        $opciones = $componente->get('data.opciones');
        $opciones['nueva'] = ['texto' => 'B', 'es_correcta' => false];

        $componente->set('data.opciones', $opciones)->call('save')->assertHasNoFormErrors();

        $this->assertSame(2, $pregunta->opciones()->count());
        $this->assertTrue($existente->fresh()->es_correcta);
    }
}
