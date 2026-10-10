<?php

namespace App\Filament\Resources\Preguntas\Schemas;

use App\Filament\Resources\Quizzes\Schemas\PreguntasRepeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PreguntaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pregunta')
                    ->description('A qué quiz pertenece y qué se le pregunta al colaborador.')
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
                        Textarea::make('enunciado')
                            ->label('Enunciado')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Opciones de respuesta')
                    ->description('Agrega las opciones aquí mismo y marca cuál es la correcta.')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->schema([
                        PreguntasRepeater::opciones()->hiddenLabel(),
                    ]),
            ]);
    }
}
