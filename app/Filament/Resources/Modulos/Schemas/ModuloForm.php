<?php

namespace App\Filament\Resources\Modulos\Schemas;

use App\Filament\Resources\Quizzes\Schemas\PreguntasRepeater;
use App\Services\QuizService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ModuloForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Módulo')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('General')
                            ->icon(Heroicon::OutlinedRectangleStack)
                            ->schema(self::general()),

                        Tab::make('Lecciones')
                            ->icon(Heroicon::OutlinedAcademicCap)
                            ->badge(fn (Get $get): ?int => count($get('lecciones') ?? []) ?: null)
                            ->schema([
                                Section::make('Lecciones del módulo')
                                    ->description(fn (Get $get): string => count($get('lecciones') ?? []) === 0
                                        ? 'Aún no hay lecciones. Usa «Agregar lección» para crear la primera; puedes seguir agregando más después.'
                                        : 'Abre una lección para editarla, arrástrala para reordenarla o agrega otra con el botón.')
                                    ->icon(Heroicon::OutlinedAcademicCap)
                                    ->schema([
                                        LeccionesRepeater::make(),
                                    ]),
                            ]),

                        Tab::make('Quiz')
                            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                            ->badge(fn (Get $get): ?int => count($get('quiz.preguntas') ?? []) ?: null)
                            ->schema(self::quiz()),
                    ]),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function general(): array
    {
        return [
            Section::make('Información del módulo')
                ->description('A qué área pertenece y de qué trata.')
                ->icon(Heroicon::OutlinedRectangleStack)
                ->columns(3)
                ->schema([
                    Select::make('area_id')
                        ->label('Área')
                        ->relationship('area', 'nombre')
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
                    TextInput::make('titulo')
                        ->label('Título')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('descripcion')
                        ->label('Descripción')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull(),
                    Toggle::make('activo')
                        ->label('Visible para los colaboradores')
                        ->default(true)
                        ->columnSpanFull(),
                ]),

            Section::make('Imagen y material')
                ->description('Opcionales: la imagen se ve en la tarjeta del módulo y el PDF lo descargan quienes lo completan.')
                ->icon(Heroicon::OutlinedPhoto)
                ->collapsible()
                ->columns(2)
                ->schema([
                    FileUpload::make('imagen')
                        ->label('Imagen del módulo')
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->directory('modulos'),
                    FileUpload::make('resumen_pdf')
                        ->label('Resumen del módulo (PDF)')
                        ->acceptedFileTypes(['application/pdf'])
                        ->directory('resumenes'),
                ]),
        ];
    }

    /**
     * El quiz es opcional: solo se crea si se le pone título, nota o preguntas.
     *
     * @return array<int, mixed>
     */
    private static function quiz(): array
    {
        return [
            Section::make('Quiz del módulo')
                ->description('Opcional. Los colaboradores lo rinden al terminar el módulo y lo aprueban con la nota mínima.')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->relationship('quiz', condition: fn (?array $state): bool => filled($state['titulo'] ?? null)
                    || filled($state['nota_minima'] ?? null)
                    || ! empty($state['preguntas']))
                ->columns(3)
                ->schema([
                    TextInput::make('titulo')
                        ->label('Título del quiz')
                        ->maxLength(255)
                        ->placeholder('Si lo dejas vacío: «Quiz: título del módulo»')
                        ->dehydrateStateUsing(fn (?string $state, Get $get): string => filled($state)
                            ? $state
                            : 'Quiz: '.$get('../titulo'))
                        ->columnSpan(2),
                    TextInput::make('nota_minima')
                        ->label('Nota mínima')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->placeholder((string) QuizService::NOTA_MINIMA_GLOBAL)
                        ->helperText('Vacío = '.QuizService::NOTA_MINIMA_GLOBAL.'%.'),
                    PreguntasRepeater::make()
                        ->columnSpanFull(),
                ]),
        ];
    }
}
