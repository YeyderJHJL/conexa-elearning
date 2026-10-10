<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * Lista de preguntas de un quiz (cada una con sus opciones) para usar dentro de un formulario.
 *
 * Las preguntas se agregan desde un modal y quedan colapsadas en la lista; al abrir una
 * se edita en el mismo lugar. Se guarda todo junto con el formulario que la contiene.
 */
class PreguntasRepeater
{
    public const MINIMO_OPCIONES = 2;

    public static function make(string $name = 'preguntas'): Repeater
    {
        return Repeater::make($name)
            ->hiddenLabel()
            ->relationship('preguntas')
            ->schema(self::campos(conRelacion: true))
            ->itemLabel(fn (array $state, Repeater $component, string $uuid): string => self::etiqueta($state, $component, $uuid))
            ->collapsible()
            ->collapsed()
            ->reorderableWithDragAndDrop()
            ->orderColumn('orden')
            ->defaultItems(0)
            ->deleteAction(fn (Action $action) => $action->requiresConfirmation()
                ->modalHeading('¿Quitar esta pregunta?')
                ->modalDescription('Se quitarán también sus opciones cuando guardes el quiz.'))
            ->addActionLabel('Agregar pregunta')
            ->addActionAlignment('start')
            ->addAction(fn (Action $action) => $action
                ->icon(Heroicon::OutlinedPlus)
                ->color('primary')
                ->modalHeading('Nueva pregunta')
                ->modalDescription('Escribe la pregunta, sus opciones y marca la correcta. Se añade al quiz al guardarlo.')
                ->modalIcon(Heroicon::OutlinedQuestionMarkCircle)
                ->modalWidth(Width::ThreeExtraLarge)
                ->modalSubmitActionLabel('Agregar pregunta')
                ->schema(self::campos(conRelacion: false))
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
     * Campos de una pregunta. `$conRelacion` es falso en el modal, donde todavía no hay un registro.
     *
     * @return array<int, mixed>
     */
    public static function campos(bool $conRelacion): array
    {
        return [
            Textarea::make('enunciado')
                ->label('Pregunta')
                ->required()
                ->rows(2)
                ->maxLength(1000)
                ->placeholder('Ej. ¿Cuál es el primer paso ante un incendio?'),
            self::opciones($conRelacion),
        ];
    }

    /**
     * Tabla de opciones de respuesta con su interruptor de "correcta".
     */
    public static function opciones(bool $conRelacion = true): Repeater
    {
        $opciones = Repeater::make('opciones')
            ->label('Opciones de respuesta')
            ->table([
                TableColumn::make('Texto de la opción')->markAsRequired(),
                TableColumn::make('Correcta')->alignCenter()->width('7rem'),
            ])
            ->schema([
                TextInput::make('texto')
                    ->required()
                    ->maxLength(300)
                    ->placeholder('Escribe una opción'),
                Toggle::make('es_correcta')
                    ->inline(false)
                    ->live()
                    ->afterStateUpdated(function (bool $state, Get $get, Set $set, Toggle $component): void {
                        // Solo una opción puede ser la correcta: al marcar una se desmarcan las demás.
                        if (! $state) {
                            return;
                        }

                        $ruta = explode('.', $component->getStatePath());
                        $propia = $ruta[count($ruta) - 2];
                        $hermanas = (array) $get('../../opciones');

                        foreach ($hermanas as $clave => $opcion) {
                            if ((string) $clave !== $propia && ! empty($opcion['es_correcta'])) {
                                $set("../../opciones.{$clave}.es_correcta", false);
                            }
                        }
                    }),
            ])
            ->minItems(self::MINIMO_OPCIONES)
            ->defaultItems(self::MINIMO_OPCIONES)
            ->addActionLabel('Agregar opción')
            ->reorderable(false)
            ->rule(fn (): Closure => self::exigeUnaCorrecta())
            ->validationMessages([
                'min' => 'Agrega al menos '.self::MINIMO_OPCIONES.' opciones.',
            ])
            ->helperText('Marca con el interruptor cuál es la respuesta correcta.');

        if ($conRelacion) {
            $opciones->relationship('opciones');
        }

        return $opciones;
    }

    /**
     * Regla: entre las opciones de una pregunta debe haber exactamente una correcta.
     */
    private static function exigeUnaCorrecta(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $correctas = collect((array) $value)->filter(fn ($opcion) => ! empty($opcion['es_correcta']))->count();

            if ($correctas !== 1) {
                $fail('Marca exactamente una opción como la respuesta correcta.');
            }
        };
    }

    /**
     * Título de cada pregunta cuando está colapsada: número, enunciado y estado de sus opciones.
     *
     * @param  array<string, mixed>  $state
     */
    private static function etiqueta(array $state, Repeater $component, string $uuid): string
    {
        $posicion = array_search($uuid, array_keys($component->getRawState()), true);
        $numero = ($posicion === false ? 0 : $posicion) + 1;

        $enunciado = Str::limit(trim((string) ($state['enunciado'] ?? '')) ?: 'Pregunta sin enunciado', 90);
        $opciones = collect($state['opciones'] ?? []);
        $correctas = $opciones->filter(fn ($opcion) => ! empty($opcion['es_correcta']))->count();

        $resumen = $opciones->count().' opciones';

        if ($correctas !== 1) {
            $resumen .= ' · ⚠ falta marcar la correcta';
        }

        return "{$numero}. {$enunciado}  —  {$resumen}";
    }
}
