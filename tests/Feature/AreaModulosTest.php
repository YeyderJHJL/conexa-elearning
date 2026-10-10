<?php

namespace Tests\Feature;

use App\Filament\Resources\Areas\AreaResource;
use App\Filament\Resources\Areas\Pages\CreateArea;
use App\Filament\Resources\Areas\Pages\EditArea;
use App\Filament\Resources\Areas\RelationManagers\ModulosRelationManager;
use App\Filament\Resources\Leccions\LeccionResource;
use App\Filament\Resources\Modulos\ModuloResource;
use App\Filament\Resources\Opcions\OpcionResource;
use App\Filament\Resources\Preguntas\PreguntaResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Area;
use App\Models\Leccion;
use App\Models\Modulo;
use App\Models\Quiz;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class AreaModulosTest extends TestCase
{
    use RefreshDatabase;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['rol' => 'admin']));

        $this->area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);
    }

    private function gestor(): Testable
    {
        return Livewire::test(ModulosRelationManager::class, [
            'ownerRecord' => $this->area,
            'pageClass' => EditArea::class,
        ]);
    }

    public function test_creating_an_area_opens_its_edit_page_to_add_modules(): void
    {
        Livewire::test(CreateArea::class)
            ->fillForm(['nombre' => 'Seguridad', 'slug' => 'seguridad', 'orden' => 1])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(AreaResource::getUrl('edit', ['record' => Area::where('slug', 'seguridad')->firstOrFail()]));
    }

    public function test_the_area_edit_page_lists_its_modules_and_nothing_else(): void
    {
        $propio = Modulo::create(['area_id' => $this->area->id, 'titulo' => 'Propio', 'orden' => 1]);
        $ajeno = Modulo::create([
            'area_id' => Area::create(['nombre' => 'Otra', 'slug' => 'otra'])->id,
            'titulo' => 'Ajeno',
            'orden' => 1,
        ]);

        $this->gestor()
            ->assertCanSeeTableRecords([$propio])
            ->assertCanNotSeeTableRecords([$ajeno]);
    }

    public function test_a_module_is_created_from_the_modal_with_the_next_order(): void
    {
        Modulo::create(['area_id' => $this->area->id, 'titulo' => 'Primero', 'orden' => 4]);

        $this->gestor()
            ->callAction(TestAction::make(CreateAction::class)->table(), data: [
                'titulo' => 'Segundo',
                'descripcion' => 'Lo básico',
                'activo' => true,
            ])
            ->assertHasNoFormErrors();

        $modulo = Modulo::where('titulo', 'Segundo')->firstOrFail();

        $this->assertSame($this->area->id, $modulo->area_id);
        $this->assertSame(5, $modulo->orden);
    }

    public function test_the_modal_requires_a_title(): void
    {
        $this->gestor()
            ->callAction(TestAction::make(CreateAction::class)->table(), data: ['titulo' => ''])
            ->assertHasFormErrors(['titulo' => 'required']);

        $this->assertSame(0, Modulo::count());
    }

    public function test_the_table_shows_lesson_and_quiz_counts(): void
    {
        $modulo = Modulo::create(['area_id' => $this->area->id, 'titulo' => 'Con material', 'orden' => 1]);
        Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'Uno', 'orden' => 1]);
        Leccion::create(['modulo_id' => $modulo->id, 'titulo' => 'Dos', 'orden' => 2]);
        Quiz::create(['modulo_id' => $modulo->id, 'titulo' => 'Quiz']);

        $this->gestor()
            ->assertTableColumnStateSet('lecciones_count', 2, $modulo)
            ->assertTableColumnStateSet('quiz_exists', true, $modulo);
    }

    public function test_content_detail_resources_are_hidden_from_the_menu_but_modules_and_quizzes_stay(): void
    {
        foreach ([LeccionResource::class, PreguntaResource::class, OpcionResource::class] as $recurso) {
            $this->assertFalse($recurso::shouldRegisterNavigation(), $recurso);
        }

        foreach ([AreaResource::class, ModuloResource::class, QuizResource::class] as $recurso) {
            $this->assertTrue($recurso::shouldRegisterNavigation(), $recurso);
        }
    }
}
