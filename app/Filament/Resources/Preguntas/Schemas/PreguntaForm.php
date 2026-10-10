<?php

namespace App\Filament\Resources\Preguntas\Schemas;

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
                    ->description('Después de crearla, agrega sus opciones y marca cuál es la correcta.')
                    ->icon(Heroicon::OutlinedQuestionMarkCircle)
                    ->columns(2)
                    ->schema([
                        Select::make('quiz_id')
                            ->label('Quiz')
                            ->relationship('quiz', 'titulo')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('enunciado')
                            ->label('Enunciado')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('orden')
                            ->label('Orden')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('El número menor aparece primero.'),
                    ]),
            ]);
    }
}
