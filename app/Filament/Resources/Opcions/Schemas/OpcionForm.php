<?php

namespace App\Filament\Resources\Opcions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class OpcionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('pregunta_id')
                    ->relationship('pregunta', 'enunciado')
                    ->required(),
                TextInput::make('texto')
                    ->required(),
                Toggle::make('es_correcta')
                    ->required(),
            ]);
    }
}
