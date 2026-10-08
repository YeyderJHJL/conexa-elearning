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
                Select::make('tipo')
                    ->options(['texto' => 'Texto', 'video' => 'Video', 'pdf' => 'PDF'])
                    ->default('texto')
                    ->required(),
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
