<?php

namespace Tests\Feature;

use App\Filament\Resources\Modulos\ModuloResource;
use App\Filament\Resources\Modulos\Pages\CreateModulo;
use App\Filament\Resources\Modulos\Pages\EditModulo;
use App\Filament\Resources\Modulos\RelationManagers\LeccionesRelationManager;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Area;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\Quiz;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ModuloFormularioTest extends TestCase
{
    use RefreshDatabase;

    private Area $area;

    private Modulo $modulo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['rol' => 'admin']));

        $this->area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);
        $this->modulo = Modulo::create(['area_id' => $this->area->id, 'titulo' => 'Introducción', 'orden' => 1]);
    }

    private function gestor(): Testable
    {
        return Livewire::test(LeccionesRelationManager::class, [
            'ownerRecord' => $this->modulo,
            'pageClass' => EditModulo::class,
        ]);
    }

    public function test_the_edit_page_shows_the_data_and_lessons_tabs(): void
    {
        $this->get(ModuloResource::getUrl('edit', ['record' => $this->modulo]))
            ->assertOk()
            ->assertSee('Datos del módulo')
            ->assertSee('Lecciones');
    }

    public function test_creating_a_module_opens_its_edit_page_to_add_lessons(): void
    {
        Livewire::test(CreateModulo::class)
            ->fillForm(['area_id' => $this->area->id, 'titulo' => 'Seguridad', 'orden' => 2])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(ModuloResource::getUrl('edit', ['record' => Modulo::where('titulo', 'Seguridad')->firstOrFail()]));
    }

    public function test_a_lesson_is_created_from_the_modal_with_the_next_order(): void
    {
        Leccion::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Anterior', 'orden' => 6]);

        $this->gestor()
            ->callAction(TestAction::make(CreateAction::class)->table(), data: [
                'titulo' => 'Bienvenida',
                'duracion_min' => 5,
                'tipo_video' => 'enlace',
                'url_video' => 'https://www.youtube.com/watch?v=abc123',
                'activa' => true,
            ])
            ->assertHasNoFormErrors();

        $leccion = Leccion::where('titulo', 'Bienvenida')->firstOrFail();

        $this->assertSame($this->modulo->id, $leccion->modulo_id);
        $this->assertSame(7, $leccion->orden);
        $this->assertSame('https://www.youtube.com/watch?v=abc123', $leccion->url_video);
    }

    public function test_only_the_chosen_video_source_is_kept(): void
    {
        $this->gestor()
            ->callAction(TestAction::make(CreateAction::class)->table(), data: [
                'titulo' => 'Sin video',
                'tipo_video' => 'ninguno',
                'url_video' => 'https://vimeo.com/123456',
                'activa' => true,
            ])
            ->assertHasNoFormErrors();

        $this->assertNull(Leccion::firstOrFail()->url_video);
    }

    public function test_a_lesson_title_is_required(): void
    {
        $this->gestor()
            ->callAction(TestAction::make(CreateAction::class)->table(), data: ['titulo' => ''])
            ->assertHasFormErrors(['titulo' => 'required']);

        $this->assertSame(0, Leccion::count());
    }

    public function test_a_lesson_can_be_edited_from_the_modal(): void
    {
        $leccion = Leccion::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Original', 'orden' => 1, 'url_video' => 'https://vimeo.com/123456']);

        $this->gestor()
            ->callAction(TestAction::make(EditAction::class)->table($leccion), data: [
                'titulo' => 'Editada',
                'tipo_video' => 'ninguno',
                'activa' => true,
            ])
            ->assertHasNoFormErrors();

        $leccion->refresh();

        $this->assertSame('Editada', $leccion->titulo);
        $this->assertNull($leccion->url_video);
        $this->assertSame(1, Leccion::count());
    }

    public function test_the_table_lists_only_the_lessons_of_its_module(): void
    {
        $propia = Leccion::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Propia', 'orden' => 1]);
        $otro = Modulo::create(['area_id' => $this->area->id, 'titulo' => 'Otro', 'orden' => 2]);
        $ajena = Leccion::create(['modulo_id' => $otro->id, 'titulo' => 'Ajena', 'orden' => 1]);

        $this->gestor()
            ->assertCanSeeTableRecords([$propia])
            ->assertCanNotSeeTableRecords([$ajena]);
    }

    public function test_the_quiz_action_creates_the_quiz_and_opens_it(): void
    {
        Livewire::test(EditModulo::class, ['record' => $this->modulo->getRouteKey()])
            ->callAction('quiz')
            ->assertRedirect(QuizResource::getUrl('edit', ['record' => Quiz::firstOrFail()]));

        $this->assertSame('Quiz: Introducción', $this->modulo->quiz->titulo);
    }

    public function test_the_quiz_action_reuses_an_existing_quiz(): void
    {
        $quiz = Quiz::create(['modulo_id' => $this->modulo->id, 'titulo' => 'Existente']);

        Livewire::test(EditModulo::class, ['record' => $this->modulo->getRouteKey()])
            ->callAction('quiz')
            ->assertRedirect(QuizResource::getUrl('edit', ['record' => $quiz]));

        $this->assertSame(1, Quiz::count());
    }
}
