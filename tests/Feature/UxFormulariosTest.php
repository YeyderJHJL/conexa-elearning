<?php

namespace Tests\Feature;

use App\Filament\Resources\Areas\AreaResource;
use App\Filament\Resources\Areas\Pages\CreateArea;
use App\Filament\Resources\Areas\Pages\EditArea;
use App\Filament\Resources\Modulos\ModuloResource;
use App\Filament\Resources\Modulos\Pages\CreateModulo;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\Area;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\TestCase;

class UxFormulariosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['rol' => 'admin']);
        $this->actingAs($this->admin);
    }

    public function test_creating_a_record_returns_to_its_table(): void
    {
        $area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);

        Livewire::test(CreateArea::class)
            ->fillForm(['nombre' => 'Seguridad', 'slug' => 'seguridad', 'orden' => 1])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(AreaResource::getUrl('index'));

        Livewire::test(CreateModulo::class)
            ->fillForm(['area_id' => $area->id, 'titulo' => 'Introducción', 'orden' => 1])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(ModuloResource::getUrl('index'));

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Ana Pérez', 'email' => 'ana@example.com', 'rol' => 'trabajador', 'password' => 'secreto-123'])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(UserResource::getUrl('index'));
    }

    public function test_editing_and_deleting_a_record_return_to_its_table(): void
    {
        $area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);

        Livewire::test(EditArea::class, ['record' => $area->getRouteKey()])
            ->fillForm(['nombre' => 'Ventas y servicio'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(AreaResource::getUrl('index'));

        $this->assertSame('Ventas y servicio', $area->refresh()->nombre);

        Livewire::test(EditArea::class, ['record' => $area->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertRedirect(AreaResource::getUrl('index'));

        $this->assertModelMissing($area);
    }

    public function test_area_slug_is_suggested_from_the_name(): void
    {
        Livewire::test(CreateArea::class)
            ->fillForm(['nombre' => 'Atención al Cliente'])
            ->assertFormSet(['slug' => 'atencion-al-cliente']);
    }

    public function test_validation_errors_in_the_admin_are_in_spanish_and_name_the_field(): void
    {
        $this->assertSame('es', app()->getLocale());

        $prueba = Livewire::test(CreateArea::class)
            ->fillForm(['nombre' => '', 'slug' => '', 'orden' => 1])
            ->call('create')
            ->assertHasFormErrors(['nombre' => 'required', 'slug' => 'required']);

        $mensajes = collect($prueba->errors()->all())->implode(' ');

        $this->assertStringContainsString('obligatorio', $mensajes);
        $this->assertStringNotContainsString('is required', $mensajes);
        $this->assertStringNotContainsString('field', $mensajes);
    }

    public function test_core_validation_messages_are_in_spanish_with_readable_field_names(): void
    {
        $errores = Validator::make(['email' => 'no-es-correo'], ['email' => 'email', 'titulo' => 'required'])->errors();

        $this->assertSame('Escribe un correo electrónico válido.', $errores->first('email'));
        $this->assertSame('El campo título es obligatorio.', $errores->first('titulo'));
    }

    public function test_worker_form_marks_the_invalid_field_and_shows_a_spanish_error(): void
    {
        $colaborador = User::factory()->create(['rol' => 'trabajador']);

        $html = $this->actingAs($colaborador)
            ->from(route('profile.edit'))
            ->followingRedirects()
            ->patch(route('profile.update'), ['name' => '', 'email' => 'esto-no-es-un-correo'])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('obligatorio', $html);
        $this->assertStringNotContainsString('field is required', $html);
    }

    public function test_login_failure_is_shown_in_spanish(): void
    {
        auth()->logout();

        $this->from('/login')
            ->followingRedirects()
            ->post('/login', ['email' => 'nadie@example.com', 'password' => 'incorrecta'])
            ->assertOk()
            ->assertSee('Estas credenciales no coinciden con nuestros registros.')
            ->assertSee('aria-invalid="true"', false);
    }
}
