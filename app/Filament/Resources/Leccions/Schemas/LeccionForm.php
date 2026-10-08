<?php

namespace App\Filament\Resources\Leccions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class LeccionForm
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
                TextInput::make('tipo')
                    ->required()
                    ->default('texto'),
                Textarea::make('contenido')
                    ->columnSpanFull(),
                TextInput::make('url_recurso'),
                TextInput::make('duracion_min')
                    ->numeric(),
                TextInput::make('orden')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('activa')
                    ->required(),
            ]);
    }
}
