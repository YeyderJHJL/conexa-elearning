<?php

namespace App\Filament\Resources\Preguntas\Schemas;

use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Formulario de una pregunta. Los mismos campos se usan en su página propia y en el modal del
 * quiz (PreguntasRelationManager), para que se vea y se comporte igual en los dos sitios.
 */
class PreguntaForm
{
    public const MINIMO_OPCIONES = 2;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Pregunta')
                    ->description('A qué quiz pertenece, en qué posición va y qué se pregunta.')
                    ->icon(Heroicon::OutlinedQuestionMarkCircle)
                    ->columns(3)
                    ->schema([
                        Select::make('quiz_id')
                            ->label('Quiz')
                            ->relationship('quiz', 'titulo')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('orden')
                            ->label('Orden')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('El número menor aparece primero.'),
                        self::enunciado()->columnSpanFull(),
                    ]),

                Section::make('Opciones de respuesta')
                    ->description('Agrégalas aquí mismo y marca cuál es la correcta.')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->schema([
                        self::opciones()->hiddenLabel(),
                    ]),
            ]);
    }

    /**
     * Campos de una pregunta sin el quiz ni el orden: los del modal del quiz.
     *
     * @return array<int, mixed>
     */
    public static function campos(): array
    {
        return [
            self::enunciado(),
            self::opciones(),
        ];
    }

    public static function enunciado(): Textarea
    {
        return Textarea::make('enunciado')
            ->label('Pregunta')
            ->required()
            ->rows(2)
            ->maxLength(1000)
            ->placeholder('Ej. ¿Cuál es el primer paso ante un incendio?');
    }

    /**
     * Tabla de opciones con su interruptor de "correcta": mínimo 2 y exactamente una correcta.
     */
    public static function opciones(): Repeater
    {
        return Repeater::make('opciones')
            ->label('Opciones de respuesta')
            ->relationship('opciones')
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

                        foreach ((array) $get('../../opciones') as $clave => $opcion) {
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
}
