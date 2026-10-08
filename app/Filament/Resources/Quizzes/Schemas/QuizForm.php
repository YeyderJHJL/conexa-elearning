<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('modulo_id')
                    ->relationship('modulo', 'titulo')
                    ->required(),
                TextInput::make('titulo')
                    ->required(),
                TextInput::make('nota_minima')
                    ->numeric(),
            ]);
    }
}
