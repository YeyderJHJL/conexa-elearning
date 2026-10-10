<?php

namespace App\Filament\Resources\Modulos\Schemas;

use App\Filament\Resources\Leccions\Schemas\LeccionForm;
use App\Filament\Support\VideoUnico;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Lista de lecciones de un módulo para usar dentro de su formulario.
 *
 * Igual que las preguntas del quiz: se agregan desde un modal, quedan colapsadas en la lista
 * y se guardan junto con el módulo.
 */
class LeccionesRepeater
{
    public static function make(string $name = 'lecciones'): Repeater
    {
        return Repeater::make($name)
            ->hiddenLabel()
            ->relationship('lecciones')
            ->schema(LeccionForm::camposAnidados())
            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => VideoUnico::normalizar($data))
            ->mutateRelationshipDataBeforeSaveUsing(function (array $data, Model $record): array {
                $data = VideoUnico::normalizar($data);

                VideoUnico::borrarAnteriorSiCambio($record->archivo_video, $data['archivo_video'] ?? null);

                return $data;
            })
            ->itemLabel(fn (array $state, Repeater $component, string $uuid): string => self::etiqueta($state, $component, $uuid))
            ->collapsible()
            ->collapsed()
            ->reorderableWithDragAndDrop()
            ->orderColumn('orden')
            ->defaultItems(0)
            ->deleteAction(fn (Action $action) => $action->requiresConfirmation()
                ->modalHeading('¿Quitar esta lección?')
                ->modalDescription('Se quitará cuando guardes el módulo.'))
            ->addActionLabel('Agregar lección')
            ->addActionAlignment('start')
            ->addAction(fn (Action $action) => $action
                ->icon(Heroicon::OutlinedPlus)
                ->color('primary')
                ->modalHeading('Nueva lección')
                ->modalDescription('Solo el título es obligatorio; el contenido, el video y el PDF se pueden completar después.')
                ->modalIcon(Heroicon::OutlinedAcademicCap)
                ->modalWidth(Width::FourExtraLarge)
                ->modalSubmitActionLabel('Agregar lección')
                ->schema(LeccionForm::camposAnidados())
                ->extraModalFooterActions(fn (Action $action): array => [
                    $action->makeModalSubmitAction('agregarOtra', arguments: ['otra' => true])
                        ->label('Agregar y crear otra'),
                ])
                ->action(function (array $data, array $arguments, Repeater $component, Action $action): void {
                    $uuid = $component->generateUuid();
                    $items = $component->getRawState();
                    $items[$uuid] = $data;

                    $component->rawState($items);
                    $component->getChildSchema($uuid)->fill($data);
                    $component->callAfterStateUpdated();

                    if ($arguments['otra'] ?? false) {
                        $action->halt();
                    }
                }));
    }

    /**
     * Título de cada lección cuando está colapsada: número, título y qué material tiene.
     *
     * @param  array<string, mixed>  $state
     */
    private static function etiqueta(array $state, Repeater $component, string $uuid): string
    {
        $posicion = array_search($uuid, array_keys($component->getRawState()), true);
        $numero = ($posicion === false ? 0 : $posicion) + 1;

        $titulo = Str::limit(trim((string) ($state['titulo'] ?? '')) ?: 'Lección sin título', 80);

        $detalles = array_filter([
            filled($state['duracion_min'] ?? null) ? $state['duracion_min'].' min' : null,
            match ($state['tipo_video'] ?? null) {
                'archivo' => 'Video',
                'enlace' => 'Video (enlace)',
                default => null,
            },
            filled($state['archivo_pdf'] ?? null) ? 'PDF' : null,
            ($state['activa'] ?? true) ? null : 'Oculta',
        ]);

        return "{$numero}. {$titulo}".($detalles ? '  —  '.implode(' · ', $detalles) : '');
    }
}
