<?php

namespace App\Filament\Resources\Opcions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OpcionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('pregunta_id')
                    ->required()
                    ->numeric(),
                TextInput::make('texto')
                    ->required(),
                Toggle::make('es_correcta')
                    ->required(),
            ]);
    }
}
