<?php

namespace Tests\Feature;

use App\Filament\Resources\Areas\Pages\CreateArea;
use App\Filament\Resources\Modulos\Pages\CreateModulo;
use App\Models\Area;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FormulariosResumenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filament.default_filesystem_disk'));
        $this->actingAs(User::factory()->create(['rol' => 'admin']));
    }

    public function test_module_form_has_an_optional_summary_upload(): void
    {
        $area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);

        Livewire::test(CreateModulo::class)
            ->assertFormFieldExists('resumen_pdf')
            ->fillForm(['area_id' => $area->id, 'titulo' => 'Sin resumen', 'orden' => 0, 'activo' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNull(Modulo::where('titulo', 'Sin resumen')->first()->resumen_pdf);
    }

    public function test_module_form_stores_the_pdf_in_the_summaries_folder(): void
    {
        $area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);

        Livewire::test(CreateModulo::class)
            ->fillForm([
                'area_id' => $area->id,
                'titulo' => 'Con resumen',
                'orden' => 0,
                'activo' => true,
                'resumen_pdf' => UploadedFile::fake()->create('resumen.pdf', 20, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $ruta = Modulo::where('titulo', 'Con resumen')->first()->resumen_pdf;

        $this->assertStringStartsWith('resumenes/', $ruta);
        Storage::disk(config('filament.default_filesystem_disk'))->assertExists($ruta);
    }

    public function test_module_form_rejects_files_that_are_not_pdf(): void
    {
        $area = Area::create(['nombre' => 'Ventas', 'slug' => 'ventas']);

        Livewire::test(CreateModulo::class)
            ->fillForm([
                'area_id' => $area->id,
                'titulo' => 'Archivo invalido',
                'orden' => 0,
                'activo' => true,
                'resumen_pdf' => UploadedFile::fake()->create('foto.png', 10, 'image/png'),
            ])
            ->call('create')
            ->assertHasFormErrors(['resumen_pdf']);

        $this->assertDatabaseMissing('modulos', ['titulo' => 'Archivo invalido']);
    }

    public function test_area_form_has_an_optional_summary_upload_that_stores_a_pdf(): void
    {
        Livewire::test(CreateArea::class)
            ->assertFormFieldExists('resumen_pdf')
            ->fillForm(['nombre' => 'Sin resumen', 'slug' => 'sin-resumen', 'orden' => 0, 'activa' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::test(CreateArea::class)
            ->fillForm([
                'nombre' => 'Con resumen',
                'slug' => 'con-resumen',
                'orden' => 0,
                'activa' => true,
                'resumen_pdf' => UploadedFile::fake()->create('resumen.pdf', 20, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNull(Area::where('slug', 'sin-resumen')->first()->resumen_pdf);

        $ruta = Area::where('slug', 'con-resumen')->first()->resumen_pdf;
        $this->assertStringStartsWith('resumenes/', $ruta);
        Storage::disk(config('filament.default_filesystem_disk'))->assertExists($ruta);
    }

    public function test_area_form_rejects_files_that_are_not_pdf(): void
    {
        Livewire::test(CreateArea::class)
            ->fillForm([
                'nombre' => 'Invalida',
                'slug' => 'invalida',
                'orden' => 0,
                'activa' => true,
                'resumen_pdf' => UploadedFile::fake()->create('foto.png', 10, 'image/png'),
            ])
            ->call('create')
            ->assertHasFormErrors(['resumen_pdf']);

        $this->assertDatabaseMissing('areas', ['slug' => 'invalida']);
    }
}
