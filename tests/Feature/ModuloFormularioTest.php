<?php

namespace Tests\Feature;

use App\Filament\Resources\Modulos\Pages\CreateModulo;
use App\Filament\Resources\Modulos\Pages\EditModulo;
use App\Models\Area;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\Opcion;
use App\Models\Quiz;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModuloFormularioTest extends TestCase
{
    use RefreshDatabase;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['rol' => 'admin']));

        $this->area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);
    }

    /**
     * @return array<string, mixed>
     */
    private function pregunta(string $enunciado): array
    {
        return [
            'enunciado' => $enunciado,
            'opciones' => [
                ['texto' => 'Opción A', 'es_correcta' => true],
                ['texto' => 'Opción B', 'es_correcta' => false],
            ],
        ];
    }

    public function test_a_module_is_created_with_lessons_and_quiz_in_one_form(): void
    {
        Livewire::test(CreateModulo::class)
            ->fillForm([
                'area_id' => $this->area->id,
                'titulo' => 'Introducción',
                'orden' => 1,
                'lecciones' => [
                    ['titulo' => 'Bienvenida', 'duracion_min' => 5, 'tipo_video' => 'enlace', 'url_video' => 'https://www.youtube.com/watch?v=abc123'],
                    ['titulo' => 'Políticas', 'tipo_video' => 'ninguno'],
                ],
                'quiz' => [
                    'titulo' => 'Quiz de introducción',
                    'nota_minima' => 80,
                    'preguntas' => [$this->pregunta('¿Primera?')],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $modulo = Modulo::firstOrFail();

        $this->assertSame(['Bienvenida', 'Políticas'], $modulo->lecciones->pluck('titulo')->all());
        $this->assertSame([1, 2], $modulo->lecciones->pluck('orden')->all());
        $this->assertSame('https://www.youtube.com/watch?v=abc123', $modulo->lecciones->first()->url_video);
        $this->assertSame('Quiz de introducción', $modulo->quiz->titulo);
        $this->assertSame(80, $modulo->quiz->nota_minima);
        $this->assertSame(['¿Primera?'], $modulo->quiz->preguntas->pluck('enunciado')->all());
        $this->assertSame(2, Opcion::count());
    }

    public function test_the_quiz_is_optional(): void
    {
        Livewire::test(CreateModulo::class)
            ->fillForm(['area_id' => $this->area->id, 'titulo' => 'Sin quiz', 'orden' => 1])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Modulo::count());
        $this->assertSame(0, Quiz::count());
    }

    public function test_the_quiz_title_defaults_to_the_module_title(): void
    {
        Livewire::test(CreateModulo::class)
            ->fillForm([
                'area_id' => $this->area->id,
                'titulo' => 'Seguridad',
                'orden' => 1,
                'quiz' => ['preguntas' => [$this->pregunta('¿Sola?')]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('Quiz: Seguridad', Quiz::firstOrFail()->titulo);
    }

    public function test_only_the_chosen_video_source_is_kept(): void
    {
        Livewire::test(CreateModulo::class)
            ->fillForm([
                'area_id' => $this->area->id,
                'titulo' => 'Módulo',
                'orden' => 1,
                'lecciones' => [
                    ['titulo' => 'Sin video', 'tipo_video' => 'ninguno', 'url_video' => 'https://vimeo.com/123456'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNull(Leccion::firstOrFail()->url_video);
    }

    public function test_a_lesson_can_be_added_from_the_modal(): void
    {
        $modulo = Modulo::create(['area_id' => $this->area->id, 'titulo' => 'Introducción', 'orden' => 1]);

        Livewire::test(EditModulo::class, ['record' => $modulo->getKey()])
            ->callAction(TestAction::make('add')->schemaComponent('lecciones'), data: [
                'titulo' => 'Desde el modal',
                'duracion_min' => 10,
                'tipo_video' => 'ninguno',
                'activa' => true,
            ])
            ->assertHasNoFormErrors()
            ->call('save')
            ->assertHasNoFormErrors();

        $leccion = $modulo->lecciones()->firstOrFail();

        $this->assertSame('Desde el modal', $leccion->titulo);
        $this->assertSame(10, $leccion->duracion_min);
    }

    public function test_editing_keeps_existing_lessons_and_quiz_by_id(): void
    {
        $modulo = Modulo::create(['area_id' => $this->area->id, 'titulo' => 'Introducción', 'orden' => 1]);
        $leccion = Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'Original', 'orden' => 1]);
        $quiz = Quiz::create(['modulo_id' => $modulo->id, 'titulo' => 'Quiz']);

        $componente = Livewire::test(EditModulo::class, ['record' => $modulo->getKey()]);
        $clave = array_key_first($componente->get('data.lecciones'));

        $componente
            ->set("data.lecciones.{$clave}.titulo", 'Editada')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Editada', $leccion->fresh()->titulo);
        $this->assertSame(1, Leccion::count());
        $this->assertSame(1, Quiz::count());
        $this->assertTrue($quiz->fresh()->is($modulo->fresh()->quiz));
    }
}
