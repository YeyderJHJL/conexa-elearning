<?php

namespace Tests\Feature;

use App\Filament\Resources\Quizzes\Pages\CreateQuiz;
use App\Filament\Resources\Quizzes\Pages\EditQuiz;
use App\Models\Area;
use App\Models\Modulo;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Quiz;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_a_quiz_is_created_with_its_questions_and_options_in_one_form(): void
    {
        Livewire::test(CreateQuiz::class)
            ->fillForm([
                'modulo_id' => $this->modulo->id,
                'titulo' => 'Quiz de inducción',
                'preguntas' => [$this->pregunta('¿Primera?'), $this->pregunta('¿Segunda?', 1)],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $quiz = Quiz::firstOrFail();

        $this->assertSame(['¿Primera?', '¿Segunda?'], $quiz->preguntas->pluck('enunciado')->all());
        $this->assertSame([1, 2], $quiz->preguntas->pluck('orden')->all());
        $this->assertSame(4, Opcion::count());
        $this->assertSame(
            ['Opción B'],
            $quiz->preguntas->last()->opciones->where('es_correcta', true)->pluck('texto')->all()
        );
    }

    public function test_a_quiz_can_be_created_without_questions(): void
    {
        Livewire::test(CreateQuiz::class)
            ->fillForm(['modulo_id' => $this->modulo->id, 'titulo' => 'Vacío'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(0, Pregunta::count());
    }

    public function test_each_question_needs_one_correct_option_and_at_least_two_options(): void
    {
        $sinCorrecta = ['enunciado' => '¿Sin correcta?', 'opciones' => [
            ['texto' => 'A', 'es_correcta' => false],
            ['texto' => 'B', 'es_correcta' => false],
        ]];
        $unaOpcion = ['enunciado' => '¿Una?', 'opciones' => [
            ['texto' => 'A', 'es_correcta' => true],
        ]];

        foreach ([$sinCorrecta, $unaOpcion] as $pregunta) {
            Livewire::test(CreateQuiz::class)
                ->fillForm(['modulo_id' => $this->modulo->id, 'titulo' => 'Quiz', 'preguntas' => [$pregunta]])
                ->call('create')
                ->assertHasFormErrors();
        }

        $this->assertSame(0, Quiz::count());
    }

    public function test_marking_an_option_as_correct_unmarks_the_others(): void
    {
        $componente = Livewire::test(CreateQuiz::class)
            ->fillForm(['preguntas' => [$this->pregunta('¿Una sola?')]]);

        $pregunta = array_key_first($componente->get('data.preguntas'));
        [$primera, $segunda] = array_keys($componente->get("data.preguntas.{$pregunta}.opciones"));

        $componente->set("data.preguntas.{$pregunta}.opciones.{$segunda}.es_correcta", true);

        $this->assertFalse($componente->get("data.preguntas.{$pregunta}.opciones.{$primera}.es_correcta"));
        $this->assertTrue($componente->get("data.preguntas.{$pregunta}.opciones.{$segunda}.es_correcta"));
    }

    public function test_a_module_cannot_get_a_second_quiz(): void
    {
        Quiz::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Primero']);

        Livewire::test(CreateQuiz::class)
            ->fillForm(['modulo_id' => $this->modulo->id, 'titulo' => 'Segundo'])
            ->call('create')
            ->assertHasFormErrors(['modulo_id' => 'unique']);
    }

    public function test_a_question_can_be_added_from_the_modal(): void
    {
        $quiz = Quiz::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Quiz']);

        Livewire::test(EditQuiz::class, ['record' => $quiz->getKey()])
            ->callAction(TestAction::make('add')->schemaComponent('preguntas'), data: $this->pregunta('¿Desde el modal?'))
            ->assertHasNoFormErrors()
            ->call('save')
            ->assertHasNoFormErrors();

        $pregunta = $quiz->preguntas()->firstOrFail();

        $this->assertSame('¿Desde el modal?', $pregunta->enunciado);
        $this->assertSame(2, $pregunta->opciones()->count());
    }

    public function test_editing_keeps_existing_questions_and_options_by_id(): void
    {
        $quiz = Quiz::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Quiz']);
        $pregunta = Pregunta::create(['quiz_id' => $quiz->id, 'enunciado' => 'Original', 'orden' => 1]);
        $correcta = Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'A', 'es_correcta' => true]);
        Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'B', 'es_correcta' => false]);

        $componente = Livewire::test(EditQuiz::class, ['record' => $quiz->getKey()]);
        $clave = array_key_first($componente->get('data.preguntas'));

        $componente
            ->set("data.preguntas.{$clave}.enunciado", 'Editada')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Editada', $pregunta->fresh()->enunciado);
        $this->assertSame(1, Pregunta::count());
        $this->assertTrue($correcta->fresh()->es_correcta);
        $this->assertSame(2, Opcion::count());
    }
}
