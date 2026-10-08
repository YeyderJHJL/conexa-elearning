<?php

namespace App\Filament\Resources\Preguntas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class PreguntaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('quiz_id')
                    ->relationship('quiz', 'titulo')
                    ->required(),
                TextInput::make('enunciado')
                    ->required(),
                TextInput::make('orden')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
